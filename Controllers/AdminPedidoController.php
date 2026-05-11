<?php
require_once __DIR__ . '/../models/AdminPedidoModel.php';
require_once __DIR__ . '/../models/PedidoLifecycleModel.php';
require_once __DIR__ . '/../config/database.php';

class AdminPedidoController {

    private $model;
    private $conn;
    private $lifecycleModel;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->model = new AdminPedidoModel($this->conn);
        $this->lifecycleModel = new PedidoLifecycleModel($this->conn);
    }

    private function expirarPedidosPendientes(): void {
        try {
            $this->lifecycleModel->expirarPendientes();
            oci_commit($this->conn);
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('SP_EXPIRAR_PEDIDOS admin: ' . $e->getMessage());
        }
    }

    public function index() {
        Auth::soloAdmin();
        $this->expirarPedidosPendientes();

        $estado = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int)$_GET['estado'] : null;
        $fecha_desde = $_GET['fecha_desde'] ?? null;
        $fecha_hasta = $_GET['fecha_hasta'] ?? null;

        $pedidos = $this->model->obtenerTodos($estado, $fecha_desde, $fecha_hasta) ?? [];
        $estados = $this->model->obtenerEstados() ?? [];

        ob_start();
        require_once __DIR__ . '/../views/admin/pedidos/index.php';
        $contenido = ob_get_clean();

        require_once __DIR__ . '/../views/admin/nav.php';
    }

    public function mapa() {
        Auth::soloAdmin();
        $this->expirarPedidosPendientes();

        $estados = $this->model->obtenerEstados() ?? [];

        ob_start();
        require_once __DIR__ . '/../views/admin/pedidos/mapa.php';
        $contenido = ob_get_clean();

        require_once __DIR__ . '/../views/admin/nav.php';
    }

    public function obtenerPedidosJson() {
        Auth::soloAdmin();
        header('Content-Type: application/json; charset=utf-8');

        $estado      = isset($_GET['estado'])     && $_GET['estado']     !== '' ? (int)$_GET['estado']     : null;
        $fecha_desde = isset($_GET['fecha_desde']) && $_GET['fecha_desde'] !== '' ? $_GET['fecha_desde']    : null;
        $fecha_hasta = isset($_GET['fecha_hasta']) && $_GET['fecha_hasta'] !== '' ? $_GET['fecha_hasta']    : null;

        $pedidos = $this->model->obtenerTodos($estado, $fecha_desde, $fecha_hasta) ?? [];
        $pedidosConDireccion = array_values(array_filter($pedidos, fn($p) => !empty($p['ciudad'])));

        echo json_encode([
            'success' => true,
            'count'   => count($pedidosConDireccion),
            'pedidos' => $pedidosConDireccion
        ]);
        exit();
    }
}
?>
