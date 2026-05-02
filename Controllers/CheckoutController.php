<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/VentaModel.php';
require_once __DIR__ . '/../models/PagoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';

class CheckoutController {

    private $conn;
    private $ventaModel;
    private $pagoModel;
    private $carritoModel;
    private $pedidoModel;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->ventaModel = new VentaModel($this->conn);
        $this->pagoModel = new PagoModel($this->conn);
        $this->carritoModel = new CarritoModel($this->conn);
        $this->pedidoModel = new PedidoModel($this->conn);
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
            $_SESSION['payment_old']
        );
        $_SESSION['carrito_count'] = 0;
    }

    public function confirmarPedido(): void {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para finalizar el pago';
            header('Location: index.php?action=login');
            exit();
        }

        $idMetodo = (int) ($_POST['metodo_pago'] ?? 0);
        if (!in_array($idMetodo, [1, 2, 3, 4], true)) {
            $_SESSION['error'] = 'Selecciona un metodo de pago valido';
            header('Location: index.php?action=pago');
            exit();
        }

        try {
            $idVenta = $this->ventaModel->crearVenta($idUsuario);
            $this->pagoModel->procesarPago($idVenta, $idMetodo);
            $this->carritoModel->vaciarCarritoTx($idUsuario);
            $pedido = $this->pedidoModel->obtenerPorVenta($idVenta);
            if (!$pedido) {
                throw new Exception('No se encontro el pedido creado para la venta');
            }

            $totalVenta = $this->ventaModel->obtenerTotalVenta($idVenta);

            oci_commit($this->conn);
            $this->limpiarSesionCheckout();

            $_SESSION['pedido_confirmado'] = [
                'id_pedido' => (int) $pedido['id_pedido'],
                'id_venta' => $idVenta,
                'total' => $totalVenta,
                'fecha_estimada_entrega' => $pedido['fecha_estimada_entrega'] ?? null,
                'metodo_pago' => $this->nombreMetodoPago($idMetodo),
                'items' => [],
                'receptor' => []
            ];

            header('Location: index.php?action=confirmacionPedido');
            exit();
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());

            $_SESSION['payment_old'] = [
                'metodo_pago' => $idMetodo > 0 ? $idMetodo : 1
            ];
            $_SESSION['error'] = 'No se pudo procesar el pago. Verifica la informacion e intenta de nuevo.';
            header('Location: index.php?action=pago');
            exit();
        }
    }
}
