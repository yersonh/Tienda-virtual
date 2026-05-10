<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PedidoLifecycleModel.php';

class PedidoLifecycleController {

    private $conn;
    private PedidoLifecycleModel $model;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->model = new PedidoLifecycleModel($this->conn);
    }

    public function expirarCron(): void {
        header('Content-Type: application/json; charset=utf-8');

        $secret = trim((string) getenv('CRON_SECRET'));
        if ($secret !== '') {
            $provided = trim((string) ($_GET['secret'] ?? ($_SERVER['HTTP_X_CRON_SECRET'] ?? '')));
            if (!hash_equals($secret, $provided)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No autorizado']);
                exit();
            }
        }

        try {
            $this->model->expirarPendientes();
            oci_commit($this->conn);
            echo json_encode(['success' => true, 'message' => 'Pedidos pendientes expirados']);
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log('expirarPedidosCron: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'No se pudieron expirar pedidos']);
        }
        exit();
    }
}
