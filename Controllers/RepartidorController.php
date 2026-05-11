<?php
require_once __DIR__ . '/../models/EntregaPedidoModel.php';
require_once __DIR__ . '/../config/database.php';

class RepartidorController {

    private $conn;
    private $model;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->model = new EntregaPedidoModel($this->conn);
    }

    // POST ?action=tomarPedido
    // Body JSON: { "id_pedido": 38, "id_repartidor": 5 }
    public function tomarPedido(): void {
        header('Content-Type: application/json; charset=utf-8');

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $idPedido     = isset($body['id_pedido'])     ? (int) $body['id_pedido']     : 0;
        $idRepartidor = isset($body['id_repartidor']) ? (int) $body['id_repartidor'] : 0;

        if ($idPedido <= 0 || $idRepartidor <= 0) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'id_pedido e id_repartidor son requeridos']);
            exit();
        }

        try {
            $this->model->tomarPedido($idPedido, $idRepartidor);
            echo json_encode(['ok' => true, 'message' => 'Pedido asignado correctamente']);
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}
?>
