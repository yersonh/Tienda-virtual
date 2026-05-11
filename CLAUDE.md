# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Tienda Virtual** (NAYLEX STORE / NAVI FLEX) is a custom PHP MVC e-commerce application built for a database management course. It features user authentication, product management, shopping carts, full checkout flow with Wompi payment gateway, order management, and an admin panel. The application uses Oracle Autonomous Database for persistence.

## Technology Stack

- **Language**: PHP 8.1+
- **Database**: Oracle Autonomous Database via OCI8 extension
- **Payment**: Wompi gateway (widget + webhook)
- **Email**: Brevo (Sendinblue) API for transactional email
- **Deployment**: Railway (Docker via `railway.json` + `Dockerfile`)

## Running the Application

```bash
php -S localhost:8080 -t public
```

Docker is used in production; locally the built-in PHP server is sufficient.

### Required Environment Variables

| Variable | Purpose |
|---|---|
| `ORACLE_USER` | Oracle DB username |
| `ORACLE_PASSWORD` | Oracle DB password |
| `ORACLE_TNS` | TNS alias (e.g. `mydb_high`) |
| `WALLET_CWALLET_B64` | Base64 of `cwallet.sso` (binary, not PEM) |
| `WALLET_EWALLET_B64` | Base64 of `ewallet.pem` or `ewallet.p12` |
| `WALLET_SQLNET_B64` | Base64 of `sqlnet.ora` |
| `WALLET_TNSNAMES_B64` | Base64 of `tnsnames.ora` |
| `WOMPI_PUBLIC_KEY` | Wompi public key for widget initialization |
| `WOMPI_PRIVATE_KEY` | Wompi private key for API verification calls |
| `WOMPI_INTEGRITY_SECRET` | Used to generate integrity signature for transactions |
| `WOMPI_EVENTS_SECRET` | Used to validate incoming webhook signatures |
| `WOMPI_TEST_MODE` | `true` for sandbox, `false` for production |
| `BREVO_API_KEY` | Brevo API key for password-recovery emails |
| `SMTP_FROM` | Sender email address |
| `SMTP_FROM_NAME` | Sender display name |

The wallet files are written to `/tmp/wallet` at runtime by `Database::getConnection()`.

## Architecture

### Routing

All requests enter through `public/index.php` via `?action=<name>`. The router uses `spl_autoload_register` to load classes by suffix (`*Controller` → `Controllers/`, `*Model` → `models/`, `Auth` → `middleware/`).

Special non-`?action` routes handled directly in `public/index.php`:
- `POST /wompi/webhook` → `WompiController::webhook()`
- `GET /cron/expirar-pedidos` → `PedidoLifecycleController::expirarCron()`

Public actions (no login required): `login`, `registro`, `guardarRegistro`, `verificarCorreo`, `verificarUsername`, `verificarTelefono`, `iniciarSesion`, `inicio`, `tienda`, `productoDetalle`, `recuperar`, `solicitarRecuperacion`, `restablecer`, `cambiarPassword`

Cart actions redirect to login if unauthenticated; JSON-accepting requests get a 401 JSON response instead of a redirect.

### Database Layer

`config/database.php` is a singleton that connects via `oci_pconnect()` and returns an `OCI8Connection` instance (defined in `config/OCI8Wrapper.php`).

`OCI8Wrapper.php` provides a PDO-like interface over OCI8:
- `OCI8Connection::prepare(string $sql)` → `OCI8Statement`
- `OCI8Statement::execute(array $params)` — binds `:name` placeholders via `oci_bind_by_name`
- `OCI8Statement::fetch()` / `fetchAll()` — returns lowercase-keyed associative arrays
- `lastInsertId()` is not supported; use `RETURNING id INTO :out_var` Oracle syntax

Transactions must be managed manually with `oci_commit($conn)` / `oci_rollback($conn)` — the wrapper uses `OCI_COMMIT_ON_SUCCESS` by default for single statements, but multi-step flows use `OCI_NO_AUTO_COMMIT` + explicit commit/rollback.

### Oracle SQL Patterns

Use Oracle-specific syntax:
```sql
-- Pagination
SELECT * FROM producto FETCH FIRST 10 ROWS ONLY
SELECT * FROM producto OFFSET 20 ROWS FETCH NEXT 10 ROWS ONLY

-- Sequence-based IDs (no auto-increment)
INSERT INTO pedido (id_pedido, ...) VALUES (seq_pedido.NEXTVAL, ...)

-- Get ID after insert
INSERT INTO pedido (...) VALUES (...) RETURNING id_pedido INTO :out_id

-- Check sequence existence
SELECT SEQUENCE_NAME FROM USER_SEQUENCES WHERE SEQUENCE_NAME = :name
```

### Controller Structure

Controllers follow a consistent pattern:
1. `require_once` model and config files at top (autoloader handles class loading in router, but controllers load their direct dependencies)
2. `__construct()` calls `Database::getConnection()` and instantiates models
3. Protected methods call `Auth::soloAdmin()` as first line
4. Views rendered with `ob_start()` / `ob_get_clean()` and then wrapped in layout (`views/layouts/navbar.php` + `views/layouts/footer.php`)

### User Roles

Three roles in `tipo_usuario`: **Admin** (id=1, full access), **Vendedor** (id=2, partial implementation), **ClienteTiendaV** (id=3, default for new registrations).

Session key `$_SESSION['logueado']` is a boolean derived from `isset($_SESSION['id_usuario'])` and re-evaluated on every request.

### Session State

Key session variables used across the checkout flow:
- `$_SESSION['carrito']` — cart items array; `$_SESSION['carrito_count']` — quantity sum
- `$_SESSION['checkout_direccion']` — selected delivery address for current checkout
- `$_SESSION['checkout_total']` / `checkout_subtotal` / `checkout_envio` — totals
- `$_SESSION['wompi_pedido_pendiente']` — pending payment metadata (cleared after confirmation)

`limpiarSesionCheckout()` in `CheckoutController` clears all `checkout_*` and `wompi_*` session keys after a successful or failed order.

### Checkout & Payment Flow

1. `?action=ConfirmarPedido` — user selects/saves delivery address
2. `?action=pago` — shows payment summary; user initiates payment
3. **JS triggers Wompi widget** (front-end, no server roundtrip)
4. `POST ?action=procesarPedido` (AJAX from payment page) → `CheckoutController::confirmarPedido()`
   - Calls `PedidoModel::crearPedidoCompletoTx()` (Oracle SP `SP_CREAR_PEDIDO_COMPLETO`)
   - Generates Wompi integrity signature (SHA256 of `reference + amount + currency + secret`)
   - Returns JSON with Wompi widget config; clears selected cart items
5. Wompi processes payment client-side; sends webhook to `POST /wompi/webhook`
6. `WompiController::webhook()` validates signatures, verifies with Wompi API, calls `WompiModel::registrarTransaccion()`, updates order status
7. On APPROVED: user is redirected to `?action=confirmacionPedido`

`PagoModel::procesarPago()` is disabled — payments only flow through the Wompi webhook.

On any exception in order creation: `oci_rollback` and redirect back to payment page.

### Password Recovery Flow

1. User submits email → `RecuperarController::solicitarRecuperacion()` generates a token stored in DB
2. `Mailer::enviarRecuperacion()` sends HTML email via Brevo REST API
3. Token link hits `?action=restablecer` → `RecuperarController::mostrarRestablecer()`
4. Form submission hits `?action=cambiarPassword` → `RecuperarController::cambiarPassword()`

### Image Handling

Product images are stored in the filesystem; `config/UploadHelper.php` manages uploads. Database references are stored in the `producto_imagen` table. `public/image.php` serves images.

### Order Expiration

`PedidoLifecycleController::expirarCron()` is triggered via `GET /cron/expirar-pedidos`. It calls `PedidoLifecycleModel` which executes the Oracle stored procedure `SP_EXPIRAR_PEDIDOS` to expire pending orders older than a threshold. This is intended to be called by an external cron job or Railway cron service.

## Deployment (Railway)

- **Build**: Docker (`Dockerfile` + `docker-entrypoint.sh`) — PHP 8.1 + OCI8 extension
- **Start**: `php -S 0.0.0.0:${PORT:-8080} -t public`
- **Health check**: `GET /?action=health` → `200 ok`
- Config: `railway.json` (Docker build, single replica, max 10 auto-restarts)
