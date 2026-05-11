<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/VentaModel.php';
require_once __DIR__ . '/../models/PagoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../models/DireccionPedidoModel.php';
require_once __DIR__ . '/../models/PedidoLifecycleModel.php';
require_once __DIR__ . '/../models/WompiApiModel.php';

class CheckoutController {

    private $conn;
    private $ventaModel;
    private $pagoModel;
    private $carritoModel;
    private $pedidoModel;
    private $direccionPedidoModel;
    private $pedidoLifecycleModel;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->ventaModel = new VentaModel($this->conn);
        $this->pagoModel = new PagoModel($this->conn);
        $this->carritoModel = new CarritoModel($this->conn);
        $this->pedidoModel = new PedidoModel($this->conn);
        $this->direccionPedidoModel = new DireccionPedidoModel($this->conn);
        $this->pedidoLifecycleModel = new PedidoLifecycleModel($this->conn);
    }

    private function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function getUsuarioId(): int {
        return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    }

    private function nombreMetodoPago(int $metodo): string {

        $sql = "SELECT FORMA_PAGO
                FROM METODO_PAGO
                WHERE ID_METODO = :id_metodo
                FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);

        if (!$stmt) {
            return 'Metodo desconocido';
        }

        oci_bind_by_name($stmt, ':id_metodo', $metodo, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            oci_free_statement($stmt);
            return 'Metodo desconocido';
        }

        $row = oci_fetch_assoc($stmt);

        oci_free_statement($stmt);

        return trim((string) ($row['FORMA_PAGO'] ?? 'Metodo desconocido'));
    }

    private function limpiarSesionCheckout(): void {
        unset(
            $_SESSION['carrito'],
            $_SESSION['carrito_mapa_cache'],
            $_SESSION['checkout_direccion_id'],
            $_SESSION['checkout_direccion_snapshot'],
            $_SESSION['checkout_fecha_estimada_entrega'],
            $_SESSION['checkout_resumen'],
            $_SESSION['payment_old']
        );
    }

    private function isJsonRequest(): bool {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return str_contains($accept, 'application/json') || $requestedWith === 'fetch' || $requestedWith === 'xmlhttprequest';
    }

    private function jsonResponse(int $statusCode, array $payload): void {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    private function baseUrl(): string {
        $proto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $https = $proto === 'https' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $host = trim((string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? ''));
        if ($host === '') {
            $host = 'localhost';
        }

        return ($https ? 'https' : 'http') . '://' . $host;
    }

    private function wompiPublicKey(): string {
        $publicKey = trim((string) getenv('WOMPI_PUBLIC_KEY'));
        if ($publicKey === '') {
            throw new RuntimeException('Falta configurar WOMPI_PUBLIC_KEY');
        }
        return $publicKey;
    }

    private function wompiIntegritySecret(): string {
        $integritySecret = trim((string) getenv('WOMPI_INTEGRITY_SECRET'));
        if ($integritySecret === '') {
            throw new RuntimeException('Falta configurar WOMPI_INTEGRITY_SECRET');
        }
        return $integritySecret;
    }

    private function wompiTestMode(): bool {
        $value = strtolower(trim((string) getenv('WOMPI_TEST_MODE')));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function validarConfiguracionWompi(string $publicKey, bool $testMode): void {
        if ($testMode && str_starts_with($publicKey, 'pub_prod_')) {
            throw new RuntimeException('WOMPI_TEST_MODE=true requiere una llave publica sandbox pub_test_');
        }
    }

    private function montoWompiCentavos(float $total): int {
        $amount = (int) round(max(0, $total) * 100);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Monto de pago invalido');
        }
        return $amount;
    }

    private function montoCheckoutWompiCentavos(float $total, bool $testMode): int {
        if ($testMode) {
            return 1500 * 100;
        }

        return $this->montoWompiCentavos($total);
    }

    private function generarReferenciaWompi(int $idPedido, int $idVenta): string {
        try {
            $suffix = strtoupper(bin2hex(random_bytes(6)));
        } catch (Throwable $e) {
            $suffix = strtoupper(substr(hash('sha256', uniqid('', true) . microtime(true)), 0, 12));
        }

        return sprintf('NVX-PED-%d-VENTA-%d-%s', $idPedido, $idVenta, $suffix);
    }

    private function firmaIntegridadWompi(string $referencia, int $amountInCents, string $currency, string $integritySecret): string {
        return hash('sha256', $referencia . (string) $amountInCents . $currency . $integritySecret);
    }

    private function correoUsuario(int $idUsuario): string {
        $query = "SELECT LOWER(TRIM(p.CORREO)) AS CORREO
                  FROM USUARIO u
                  INNER JOIN PERSONA p ON p.ID_PERSONA = u.ID_PERSONA
                  WHERE u.ID_USUARIO = :id_usuario
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception('No se pudo consultar el correo del usuario');
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el correo del usuario');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        $correo = trim((string) ($row['CORREO'] ?? ''));
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Tu usuario no tiene un correo valido para Wompi');
        }

        return $correo;
    }

    private function metodoPendienteWompi(): int {
        return 3;
    }

    private function expirarPedidosPendientes(): void {
        try {
            $this->pedidoLifecycleModel->expirarPendientes();
            oci_commit($this->conn);
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('SP_EXPIRAR_PEDIDOS checkout: ' . $e->getMessage());
        }
    }

    private function pagoAprobadoExiste(int $idVenta): bool {
        $query = "SELECT COUNT(*) AS TOTAL
                  FROM PAGO
                  WHERE ID_VENTA = :id_venta
                    AND UPPER(TRIM(ESTADO)) IN ('APPROVED', 'PAGADO', 'COMPLETADO')";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception('No se pudo validar el pago del pedido');
        }

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo validar el pago del pedido');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return ((int) ($row['TOTAL'] ?? 0)) > 0;
    }

    private function obtenerPedidoPendienteUsuario(int $idUsuario, int $idPedido): ?array {
        $query = "SELECT p.ID_PEDIDO,
                         p.ID_VENTA,
                         p.ID_ESTADO,
                         NVL(v.TOTAL, 0) AS TOTAL,
                         TO_CHAR(p.CREATED_AT, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT,
                         GREATEST(0, FLOOR((CAST(p.CREATED_AT AS DATE) + (30 / 1440) - SYSDATE) * 86400)) AS SEGUNDOS_RESTANTES
                  FROM PEDIDO p
                  INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                  WHERE p.ID_PEDIDO = :id_pedido
                    AND v.ID_USUARIO = :id_usuario
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception('No se pudo preparar la consulta del pedido pendiente');
        }

        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el pedido pendiente');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function checkoutPayloadWompi(int $idPedido, int $idVenta, float $total): array {
        $publicKey = $this->wompiPublicKey();
        $integritySecret = $this->wompiIntegritySecret();
        $wompiTestMode = $this->wompiTestMode();
        $this->validarConfiguracionWompi($publicKey, $wompiTestMode);

        $currency = 'COP';
        $realAmountInCents = $this->montoWompiCentavos($total);
        $amountInCents = $this->montoCheckoutWompiCentavos($total, $wompiTestMode);
        $referencia = $this->generarReferenciaWompi($idPedido, $idVenta);
        $integritySignature = $this->firmaIntegridadWompi($referencia, $amountInCents, $currency, $integritySecret);
        $returnUrl = $this->baseUrl() . '/index.php?action=misPedidos&id=' . $idPedido;

        return [
            'public_key' => $publicKey,
            'currency' => $currency,
            'amount_in_cents' => $amountInCents,
            'real_amount_in_cents' => $realAmountInCents,
            'test_mode' => $wompiTestMode,
            'reference' => $referencia,
            'integrity_signature' => $integritySignature,
            'redirect_url' => $returnUrl,
            'return_url' => $returnUrl
        ];
    }

    private function resumenCompra(array $items): array {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['precio'] ?? 0) * (int) ($item['cantidad'] ?? 0);
        }

        $subtotal = round(max(0, $subtotal), 2);
        $iva = round($subtotal * 0.19, 2);
        $envio = 0;

        if (isset($_SESSION['checkout_resumen']) && is_array($_SESSION['checkout_resumen'])) {
            $envio = max(0, (float) ($_SESSION['checkout_resumen']['envio'] ?? 0));
        }

        return [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'envio' => $envio,
            'total' => round($subtotal + $iva + $envio, 2)
        ];
    }

    private function validarItemsCheckout(array $items): void {
        if (empty($items)) {
            throw new Exception('Tu carrito esta vacio');
        }

        foreach ($items as $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $stock = (int) ($item['stock_p'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);

            if ($idProducto <= 0 || $cantidad <= 0 || $precio < 0) {
                throw new Exception('El carrito contiene productos invalidos');
            }

            if ((int) ($item['id_referencia'] ?? 0) <= 0) {
                throw new Exception('El carrito contiene referencias invalidas');
            }

            if ($stock < $cantidad) {
                throw new Exception('Stock insuficiente para la referencia ' . (int) ($item['id_referencia'] ?? 0));
            }
        }
    }

    private function itemsConfirmacion(array $items): array {
        return array_map(function ($item) {
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);

            return [
                'id_producto' => (int) ($item['id_producto'] ?? 0),
                'id_referencia' => (int) ($item['id_referencia'] ?? 0),
                'numero_referencia' => (string) ($item['numero_referencia'] ?? ''),
                'nombre' => (string) ($item['nombre'] ?? 'Producto'),
                'cantidad' => $cantidad,
                'precio' => $precio,
                'subtotal' => round($cantidad * $precio, 2)
            ];
        }, $items);
    }

    private function receptorConfirmacion(array $direccion): array {
        return [
            'nombre' => trim((string) (($direccion['nombre_receptor'] ?? '') . ' ' . ($direccion['apellido_receptor'] ?? ''))),
            'direccion' => (string) ($direccion['direccion_envio'] ?? ''),
            'ciudad' => (string) ($direccion['ciudad'] ?? ''),
            'telefono' => (string) ($direccion['telefono_receptor'] ?? '')
        ];
    }

    private function eliminarPagoPendienteSinReferencia(int $idVenta): void {
        $query = "DELETE FROM PAGO
                  WHERE ID_VENTA = :id_venta
                    AND (REFERENCIA_WOMPI IS NULL OR TRIM(TO_CHAR(REFERENCIA_WOMPI)) = '')
                    AND (ID_TRANSACCION_WOMPI IS NULL OR TRIM(TO_CHAR(ID_TRANSACCION_WOMPI)) = '')
                    AND UPPER(TRIM(NVL(TO_CHAR(ESTADO), ''))) NOT IN ('APPROVED', 'PAGADO', 'COMPLETADO')";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            return;
        }

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        @oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmt);
    }

    public function confirmarPedido(): void {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $this->jsonResponse(401, [
                'success'  => false,
                'message'  => 'Debes iniciar sesion para finalizar el pago',
                'redirect' => 'index.php?action=login'
            ]);
        }

        $idMetodo = $this->metodoPendienteWompi();

        $idDireccion = (int) ($_SESSION['checkout_direccion_id'] ?? 0);
        if ($idDireccion <= 0) {
            $this->jsonResponse(400, [
                'success'  => false,
                'message'  => 'Selecciona una direccion antes de pagar',
                'redirect' => 'index.php?action=ConfirmarPedido'
            ]);
        }

        $direccion = $this->direccionPedidoModel->obtenerDireccionPorId($idDireccion, $idUsuario);
        if (!$direccion) {
            unset($_SESSION['checkout_direccion_id'], $_SESSION['checkout_direccion_snapshot']);
            $this->jsonResponse(400, [
                'success'  => false,
                'message'  => 'La direccion seleccionada no esta disponible',
                'redirect' => 'index.php?action=ConfirmarPedido'
            ]);
        }

        try {
            $items = $this->carritoModel->obtenerItemsCheckoutTx($idUsuario);
            $this->validarItemsCheckout($items);
            $resumen = $this->resumenCompra($items);
            $fechaEstimada = $_SESSION['checkout_fecha_estimada_entrega'] ?? null;

            $resultadoPedido = $this->pedidoModel->crearPedidoCompletoTx(
                $idUsuario,
                $idDireccion,
                $idMetodo,
                $fechaEstimada,
                $resumen
            );

            $idPedido = (int) ($resultadoPedido['id_pedido'] ?? 0);
            $idVenta  = (int) ($resultadoPedido['id_venta']  ?? 0);
            $pedido   = $idPedido > 0
                ? $this->pedidoModel->obtenerPorId($idPedido)
                : ($idVenta > 0 ? $this->pedidoModel->obtenerPorVenta($idVenta) : null);

            if (!$pedido) {
                throw new Exception('No se encontro el pedido creado por SP_CREAR_PEDIDO_COMPLETO');
            }

            $idPedido = (int) ($pedido['id_pedido'] ?? $idPedido);
            $idVenta  = (int) ($pedido['id_venta']  ?? $idVenta);

            if ($idPedido <= 0 || $idVenta <= 0) {
                throw new Exception('No se pudo preparar el pedido para Wompi');
            }

            // Eliminar cualquier PAGO creado por el SP sin referencia Wompi —
            // el registro real de PAGO solo debe crearse desde el webhook.
            $this->eliminarPagoPendienteSinReferencia($idVenta);

            // Asegurar que el pedido quede en estado 1 (pendiente) tras el SP.
            $this->pedidoModel->mantenerPendienteTx($idPedido);

            $checkoutPayload = $this->checkoutPayloadWompi($idPedido, $idVenta, (float) $resumen['total']);

            $this->carritoModel->eliminarSeleccionadosTx($idUsuario);

            oci_commit($this->conn);
            $this->limpiarSesionCheckout();
            $_SESSION['carrito_count'] = array_sum($this->carritoModel->obtenerMapaCarritoUsuario($idUsuario));
            $_SESSION['wompi_pedido_pendiente'] = [
                'id_pedido'          => $idPedido,
                'id_venta'           => $idVenta,
                'referencia'         => $checkoutPayload['reference'],
                'amount_in_cents'    => $checkoutPayload['amount_in_cents'],
                'real_amount_in_cents' => $checkoutPayload['real_amount_in_cents'],
                'test_mode'          => $checkoutPayload['test_mode'],
                'fecha'              => date('Y-m-d H:i:s'),
                'total'              => $resumen['total'],
                'currency'           => $checkoutPayload['currency']
            ];

            $this->jsonResponse(200, [
                'success'   => true,
                'message'   => 'Pedido pendiente creado. Completa el pago en Wompi.',
                'id_pedido' => $idPedido,
                'id_venta'  => $idVenta,
                'checkout'  => $checkoutPayload
            ]);
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log('confirmarPedido: ' . $e->getMessage());

            $message = $e instanceof InvalidArgumentException || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'No se pudo preparar el pago con Wompi. Intenta de nuevo.';

            $this->jsonResponse(400, [
                'success' => false,
                'message' => $message
            ]);
        }
    }

    public function reintentarPago(): void {
        $this->ensureSession();
        $this->expirarPedidosPendientes();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para pagar el pedido';
            header('Location: index.php?action=login');
            exit();
        }

        $idPedido = (int) ($_GET['id'] ?? 0);
        if ($idPedido <= 0) {
            $_SESSION['error'] = 'Pedido invalido';
            header('Location: index.php?action=misPedidos');
            exit();
        }

        try {
            $pedidoPendiente = $this->obtenerPedidoPendienteUsuario($idUsuario, $idPedido);
            if (!$pedidoPendiente) {
                throw new InvalidArgumentException('Pedido no encontrado');
            }

            $idEstado = (int) ($pedidoPendiente['id_estado'] ?? 0);
            $idVenta = (int) ($pedidoPendiente['id_venta'] ?? 0);
            $total = (float) ($pedidoPendiente['total'] ?? 0);
            $segundosRestantes = (int) ($pedidoPendiente['segundos_restantes'] ?? 0);

            if ($idEstado !== 1) {
                throw new InvalidArgumentException($idEstado === 5 ? 'Este pedido ya fue cancelado o expiro.' : 'Este pedido ya no esta pendiente de pago.');
            }

            if ($segundosRestantes <= 0) {
                throw new InvalidArgumentException('Este pedido pendiente ya expiro. Vuelve a crear la compra desde el carrito.');
            }

            if ($idVenta <= 0 || $total <= 0) {
                throw new InvalidArgumentException('El pedido no tiene una venta valida para reintentar el pago.');
            }

            if ($this->pagoAprobadoExiste($idVenta)) {
                throw new InvalidArgumentException('Este pedido ya tiene un pago aprobado.');
            }

            $checkoutPayload = $this->checkoutPayloadWompi($idPedido, $idVenta, $total);
            $_SESSION['wompi_pedido_pendiente'] = [
                'id_pedido' => $idPedido,
                'id_venta' => $idVenta,
                'referencia' => $checkoutPayload['reference'],
                'amount_in_cents' => $checkoutPayload['amount_in_cents'],
                'real_amount_in_cents' => $checkoutPayload['real_amount_in_cents'],
                'test_mode' => $checkoutPayload['test_mode'],
                'fecha' => date('Y-m-d H:i:s'),
                'total' => $total,
                'currency' => $checkoutPayload['currency'],
                'retry' => true
            ];

            require_once __DIR__ . '/../views/pagos/reintentar_wompi.php';
            exit();
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = $e instanceof InvalidArgumentException || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'No se pudo preparar el reintento de pago.';
            header('Location: index.php?action=misPedidos&id=' . $idPedido);
            exit();
        }
    }
}
