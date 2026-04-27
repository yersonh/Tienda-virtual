# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Tienda Virtual** is a custom PHP MVC e-commerce application built for a database management course. It features user authentication, product management, shopping carts, and an admin panel. The application uses PostgreSQL for data persistence and is designed to run on PHP's built-in server.

## Technology Stack

- **Language**: PHP 8.1+
- **Database**: PostgreSQL
- **Required PHP Extensions**: pdo_pgsql, pdo, mbstring, gd, curl, xml, zip
- **Deployment**: Railway (using Nixpacks build system)
- **Database Credentials**: Hardcoded in `config/database.php` (for Railway PostgreSQL instance)

## Architecture

### Directory Structure

- **Controllers/** - Business logic layer. Each controller handles a specific feature area:
  - `LoginController.php` - User authentication
  - `RegistroController.php` - User registration
  - `ProductoController.php` - Product CRUD operations (admin only)
  - `TiendaController.php` - Store front and product listings
  - `CarritoController.php` - Shopping cart operations
  - `PerfilController.php` - User profile management
  - `AdminController.php` - Admin dashboard

- **models/** - Database access layer using PDO prepared statements:
  - Each model class wraps database operations for its entity
  - Constructor receives PDO connection instance
  - Methods use parameterized queries with named placeholders (`:id` syntax)
  - Returns associative arrays via PDO::FETCH_ASSOC

- **views/** - PHP templates with output buffering:
  - Split between public views (`Inicio.php`, `Login.php`, `Tienda.php`, etc.)
  - Admin views in `views/admin/` subdirectory
  - Shared layouts in `views/layouts/` (navbar, footer)
  - Controllers use `ob_start()`/`ob_get_clean()` to buffer view output
  - Views are wrapped in admin navigation template for admin pages

- **middleware/** - `Auth.php` provides access control:
  - `Auth::soloAdmin()` - Restricts actions to admin users (type 'Admin')
  - Checks `$_SESSION['id_usuario']` for authentication state

- **config/** - Configuration and helpers:
  - `database.php` - Database singleton using PDO with Railway PostgreSQL
  - `UploadHelper.php` - File upload utilities for product images

- **public/** - Web-accessible entry point:
  - `index.php` - Simple router using GET `?action=` parameter
  - `image.php` - Image serving helper
  - All requests route through `index.php`

- **init.sql** - Database schema initialization with tables: persona, usuario, tipo_usuario, proveedor, categoria_producto, producto, producto_imagen, carrito, detalle_carrito

### Routing Pattern

The application uses a simple query-string based router in `public/index.php`:

```
/?action=tienda           # Store front
/?action=productos        # Admin product list
/?action=login            # Login form
/?action=iniciarSesion    # POST login handler
/?action=agregarCarrito   # Add to cart (AJAX)
/?action=verCarrito       # View cart
/?action=perfil           # User profile
```

Public actions (no login required): login, registro, tienda, productoDetalle, carrito operations, email/username verification
Protected actions: All admin routes, profile, and other authenticated features

## Running the Application

### Local Development

**Start the PHP built-in server** (runs on http://localhost:8080):
```bash
php -S localhost:8080 -t public
```

The server automatically uses `public/index.php` as the entry point for all requests.

### Database Setup

**Initialize the PostgreSQL database schema**:
```bash
psql -U postgres -d railway -f init.sql
```

Update `config/database.php` with your local database credentials to develop locally.

### Testing the Application

Manual testing workflow:
1. Start the server: `php -S localhost:8080 -t public`
2. Test public routes: registration, login, product browsing
3. Create test users with different roles (Admin, Vendedor, ClienteTiendaV)
4. Test admin-protected routes as Admin user
5. Test shopping cart and user profile flows
6. Verify authentication redirects for protected pages

## Code Patterns and Conventions

### Database Queries

Models use PDO prepared statements with named parameters:
```php
$query = "SELECT * FROM usuario WHERE id_usuario = :id";
$stmt = $this->conn->prepare($query);
$stmt->execute([':id' => $id]);
```

Always use parameterized queries to prevent SQL injection.

### Controller Structure

Controllers:
1. Require model and config files at the top
2. Instantiate model in `__construct()` with PDO connection
3. Each public method corresponds to a router action
4. Use output buffering with `ob_start()` / `ob_get_clean()` for views
5. Call `Auth::soloAdmin()` at the start of protected methods
6. Render views within the admin navigation template for admin pages

### View Pattern

Views are PHP templates with access to variables from the controller:
```php
// In Controller:
$productos = $this->model->obtenerTodos();
ob_start();
require_once __DIR__ . '/../views/admin/productos/index.php';
```

Views render HTML and expect variables to be in scope.

### User Roles

Three user types in `tipo_usuario`:
- **Admin** - Full system access, product management
- **Vendedor** - Seller role (setup exists but may not be fully implemented)
- **ClienteTiendaV** - Customer role (default for registrations)

## Important Implementation Details

- **Session Management**: Uses native PHP sessions (`$_SESSION`). Session must be started in `public/index.php` before any routing logic.
- **Image Handling**: Product images stored in file system with database references in `producto_imagen` table. Upload helper manages file operations.
- **Cart Storage**: Shopping cart in `carrito` and `detalle_carrito` tables (not session-based).
- **No Framework**: This is a custom MVC implementation without using Laravel, Symfony, or similar frameworks.

## Deployment (Railway)

The application is configured for Railway deployment:
- **Build**: Uses Nixpacks (defined in `nixpacks.toml`) with PHP 8.1 and required extensions
- **Start Command**: `php -S 0.0.0.0:${PORT:-8080} -t public`
- **Health Check**: Monitors `/` endpoint
- **Restart Policy**: Auto-restarts on failure (max 10 retries)

Configuration files:
- `railway.json` - Railway-specific build and deploy settings
- `nixpacks.toml` - Build environment specification (PHP version, extensions, env vars)
