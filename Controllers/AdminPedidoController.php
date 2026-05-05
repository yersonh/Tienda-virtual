<?php
require_once __DIR__ . '/../models/AdminPedidoModel.php';
require_once __DIR__ . '/../config/database.php';

class AdminPedidoController {

    private $model;

    public function __construct() {
        $pdo = Database::getConnection();
        $this->model = new AdminPedidoModel($pdo);
    }

    public function index() {
        Auth::soloAdmin();

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

        $pedidos = $this->model->obtenerTodos(null, null, null) ?? [];
        $estados = $this->model->obtenerEstados() ?? [];

        $pedidosConDireccion = array_filter($pedidos, fn($p) => !empty($p['ciudad']));

        ob_start();
        require_once __DIR__ . '/../views/admin/pedidos/mapa.php';
        $contenido = ob_get_clean();

        require_once __DIR__ . '/../views/admin/nav.php';
    }
}
?>
