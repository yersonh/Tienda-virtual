<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/VentaModel.php';
require_once __DIR__ . '/../models/PagoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../models/DireccionPedidoModel.php';
require_once __DIR__ . '/../models/MetodoPagoUsuarioModel.php';

class CheckoutController {

    private $conn;
    private $ventaModel;
    private $pagoModel;
    private $carritoModel;
    private $pedidoModel;
    private $direccionPedidoModel;
    private $metodoPagoUsuarioModel;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->ventaModel = new VentaModel($this->conn);
        $this->pagoModel = new PagoModel($this->conn);
        $this->carritoModel = new CarritoModel($this->conn);
        $this->pedidoModel = new PedidoModel($this->conn);
        $this->direccionPedidoModel = new DireccionPedidoModel($this->conn);
        $this->metodoPagoUsuarioModel = new MetodoPagoUsuarioModel($this->conn);
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
        return match ($metodo) {
            1 => 'Efectivo',
            2 => 'Tarjeta debito',
            3 => 'Tarjeta credito',
            4 => 'Transferencia bancaria',
            default => 'Registrado'
        };
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
            return 100 * 100;
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

    private function metodoPendienteWompi(): int {
        return 3;
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

    public function confirmarPedido(): void {
        $this->ensureSession();
        $jsonRequest = $this->isJsonRequest();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            if ($jsonRequest) {
                $this->jsonResponse(401, [
                    'success' => false,
                    'message' => 'Debes iniciar sesion para finalizar el pago',
                    'redirect' => 'index.php?action=login'
                ]);
            }
            $_SESSION['error'] = 'Debes iniciar sesion para finalizar el pago';
            header('Location: index.php?action=login');
            exit();
        }

        $idMetodo = $this->metodoPendienteWompi();

        $idDireccion = (int) ($_SESSION['checkout_direccion_id'] ?? 0);
        if ($idDireccion <= 0) {
            if ($jsonRequest) {
                $this->jsonResponse(400, [
                    'success' => false,
                    'message' => 'Selecciona una direccion antes de pagar',
                    'redirect' => 'index.php?action=ConfirmarPedido'
                ]);
            }
            $_SESSION['error'] = 'Selecciona una direccion antes de pagar';
            header('Location: index.php?action=ConfirmarPedido');
            exit();
        }

        $direccion = $this->direccionPedidoModel->obtenerDireccionPorId($idDireccion, $idUsuario);
        if (!$direccion) {
            unset($_SESSION['checkout_direccion_id'], $_SESSION['checkout_direccion_snapshot']);
            if ($jsonRequest) {
                $this->jsonResponse(400, [
                    'success' => false,
                    'message' => 'La direccion seleccionada no esta disponible',
                    'redirect' => 'index.php?action=ConfirmarPedido'
                ]);
            }
            $_SESSION['error'] = 'La direccion seleccionada no esta disponible';
            header('Location: index.php?action=ConfirmarPedido');
            exit();
        }

        try {
            $publicKey = $this->wompiPublicKey();
            $integritySecret = $this->wompiIntegritySecret();
            $wompiTestMode = $this->wompiTestMode();
            $this->validarConfiguracionWompi($publicKey, $wompiTestMode);

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
            $idVenta = (int) ($resultadoPedido['id_venta'] ?? 0);
            $pedido = $idPedido > 0 ? $this->pedidoModel->obtenerPorId($idPedido) : ($idVenta > 0 ? $this->pedidoModel->obtenerPorVenta($idVenta) : null);
            if (!$pedido) {
                throw new Exception('No se encontro el pedido creado por SP_CREAR_PEDIDO_COMPLETO');
            }
            $idPedido = (int) ($pedido['id_pedido'] ?? $idPedido);
            $idVenta = (int) ($pedido['id_venta'] ?? $idVenta);

            if ($idPedido <= 0 || $idVenta <= 0) {
                throw new Exception('No se pudo preparar el pedido para Wompi');
            }

            $currency = 'COP';
            $realAmountInCents = $this->montoWompiCentavos((float) $resumen['total']);
            $amountInCents = $this->montoCheckoutWompiCentavos((float) $resumen['total'], $wompiTestMode);
            $referencia = $this->generarReferenciaWompi($idPedido, $idVenta);
            $integritySignature = $this->firmaIntegridadWompi($referencia, $amountInCents, $currency, $integritySecret);
            $returnUrl = $this->baseUrl() . '/index.php?action=misPedidos&id=' . $idPedido;

            $this->carritoModel->eliminarSeleccionadosTx($idUsuario);

            oci_commit($this->conn);
            $this->limpiarSesionCheckout();
            $_SESSION['carrito_count'] = array_sum($this->carritoModel->obtenerMapaCarritoUsuario($idUsuario));
            $_SESSION['wompi_pedido_pendiente'] = [
                'id_pedido' => $idPedido,
                'id_venta' => $idVenta,
                'referencia' => $referencia,
                'amount_in_cents' => $amountInCents,
                'real_amount_in_cents' => $realAmountInCents,
                'test_mode' => $wompiTestMode,
                'fecha' => date('Y-m-d H:i:s'),
                'total' => $resumen['total'],
                'currency' => $currency
            ];

            $payload = [
                'success' => true,
                'message' => 'Pedido pendiente creado. Completa el pago en Wompi.',
                'id_pedido' => $idPedido,
                'id_venta' => $idVenta,
                'checkout' => [
                    'public_key' => $publicKey,
                    'currency' => $currency,
                    'amount_in_cents' => $amountInCents,
                    'real_amount_in_cents' => $realAmountInCents,
                    'test_mode' => $wompiTestMode,
                    'reference' => $referencia,
                    'integrity_signature' => $integritySignature,
                    'redirect_url' => $returnUrl,
                    'return_url' => $returnUrl
                ]
            ];

            if ($jsonRequest) {
                $this->jsonResponse(200, $payload);
            }

            $_SESSION['success'] = 'Pedido pendiente creado. Completa el pago en Wompi para activar la factura.';
            header('Location: index.php?action=misPedidos&id=' . $idPedido);
            exit();
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());

            $message = $e instanceof InvalidArgumentException || $e instanceof RuntimeException
                ? $e->getMessage()
                : 'No se pudo preparar el pago con Wompi. Intenta de nuevo.';

            if ($jsonRequest) {
                $this->jsonResponse(400, [
                    'success' => false,
                    'message' => $message
                ]);
            }

            $_SESSION['error'] = $message;
            header('Location: index.php?action=pago');
            exit();
        }
    }
}
