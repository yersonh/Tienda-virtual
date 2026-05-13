<?php
require_once __DIR__ . '/../config/database.php';

class PollController {

    private $conn;

    public function __construct() {
        $this->conn = Database::getConnection();
    }

    private function json(array $data): never {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    private function userId(): int {
        return (int) ($_SESSION['id_usuario'] ?? 0);
    }

    private static function estadoNombre(int $id): string {
        return match ($id) {
            1 => 'Pendiente de pago',
            2 => 'Procesado',
            3 => 'Enviado',
            4 => 'Entregado',
            5 => 'Cancelado',
            default => 'Desconocido'
        };
    }

    /** GET ?action=pollPedidoEstado&id=X — estado actual de un pedido propio */
    public function pedidoEstado(): void {
        $idUsuario = $this->userId();
        $idPedido  = (int) ($_GET['id'] ?? 0);

        if ($idUsuario <= 0 || $idPedido <= 0) {
            $this->json(['ok' => false]);
        }

        $sql = "SELECT p.ID_PEDIDO, p.ID_ESTADO,
                       GREATEST(0, FLOOR((CAST(p.CREATED_AT AS DATE) + (20 / 1440) - SYSDATE) * 86400)) AS SEGUNDOS_RESTANTES
                FROM PEDIDO p
                INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                WHERE p.ID_PEDIDO = :id_pedido
                  AND v.ID_USUARIO = :id_usuario
                FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':id_pedido',  $idPedido,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            oci_free_statement($stmt);
            $this->json(['ok' => false]);
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            $this->json(['ok' => false]);
        }

        $idEstado = (int) $row['ID_ESTADO'];
        $this->json([
            'ok'                 => true,
            'id_pedido'          => (int) $row['ID_PEDIDO'],
            'id_estado'          => $idEstado,
            'estado_nombre'      => self::estadoNombre($idEstado),
            'segundos_restantes' => (int) $row['SEGUNDOS_RESTANTES'],
        ]);
    }

    /** GET ?action=pollPedidosUsuario — estados de todos los pedidos del usuario autenticado */
    public function pedidosUsuario(): void {
        $idUsuario = $this->userId();
        if ($idUsuario <= 0) {
            $this->json(['ok' => false, 'pedidos' => []]);
        }

        $sql = "SELECT p.ID_PEDIDO, p.ID_ESTADO,
                       GREATEST(0, FLOOR((CAST(p.CREATED_AT AS DATE) + (20 / 1440) - SYSDATE) * 86400)) AS SEGUNDOS_RESTANTES
                FROM PEDIDO p
                INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                WHERE v.ID_USUARIO = :id_usuario
                ORDER BY p.ID_PEDIDO DESC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            oci_free_statement($stmt);
            $this->json(['ok' => false, 'pedidos' => []]);
        }

        $pedidos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $idEstado  = (int) $row['ID_ESTADO'];
            $pedidos[] = [
                'id_pedido'          => (int) $row['ID_PEDIDO'],
                'id_estado'          => $idEstado,
                'estado_nombre'      => self::estadoNombre($idEstado),
                'segundos_restantes' => (int) $row['SEGUNDOS_RESTANTES'],
            ];
        }
        oci_free_statement($stmt);

        $this->json(['ok' => true, 'pedidos' => $pedidos]);
    }

    /** GET ?action=pollAdminPedidos&ids=1,2,3 — estados de pedidos visibles en el panel admin */
    public function adminPedidos(): void {
        if ((int) ($_SESSION['tipo_usuario'] ?? 0) !== 1) {
            $this->json(['ok' => false]);
        }

        $idsRaw = trim((string) ($_GET['ids'] ?? ''));
        if ($idsRaw === '') {
            $this->json(['ok' => true, 'pedidos' => []]);
        }

        $ids = array_values(array_unique(
            array_filter(array_map('intval', explode(',', $idsRaw)), fn($id) => $id > 0)
        ));

        if (empty($ids)) {
            $this->json(['ok' => true, 'pedidos' => []]);
        }

        $placeholders = [];
        $params       = [];
        foreach ($ids as $index => $id) {
            $key              = ':id' . $index;
            $placeholders[]   = $key;
            $params[$key]     = $id;
        }

        $sql  = 'SELECT ID_PEDIDO, ID_ESTADO FROM PEDIDO WHERE ID_PEDIDO IN (';
        $sql .= implode(', ', $placeholders) . ')';

        $stmt = oci_parse($this->conn, $sql);
        foreach ($params as $key => $val) {
            oci_bind_by_name($stmt, $key, $params[$key], -1, SQLT_INT);
        }

        if (!@oci_execute($stmt)) {
            oci_free_statement($stmt);
            $this->json(['ok' => false, 'pedidos' => []]);
        }

        $pedidos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $idEstado  = (int) $row['ID_ESTADO'];
            $pedidos[] = [
                'id_pedido'     => (int) $row['ID_PEDIDO'],
                'id_estado'     => $idEstado,
                'estado_nombre' => self::estadoNombre($idEstado),
            ];
        }
        oci_free_statement($stmt);

        $this->json(['ok' => true, 'pedidos' => $pedidos]);
    }
}
