<?php
class AdminPedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(?int $estado = null, ?string $fecha_desde = null, ?string $fecha_hasta = null): array {
        $base = "SELECT vp.ID_PEDIDO,
                       vp.ID_ESTADO,
                       vp.ESTADO AS ESTADO_NOMBRE,
                       TO_CHAR(vp.FECHA, 'YYYY-MM-DD HH24:MI:SS') AS FECHA,
                       NVL(vp.TOTAL, 0) AS TOTAL,
                       NVL(vp.IVA, 0) AS IVA,
                       NVL(vp.ENVIO, 0) AS ENVIO,
                       vp.CLIENTE_NOMBRE,
                       vp.CLIENTE_APELLIDO,
                       vp.NOMBRE_RECEPTOR,
                       vp.APELLIDO_RECEPTOR,
                       vp.DIRECCION_ENVIO,
                       vp.CIUDAD,
                       vp.BARRIO,
                       vp.TELEFONO_RECEPTOR,
                       vp.INFORMACION_ADICIONAL,
                       TO_CHAR(vp.FECHA_ESTIMADA_ENTREGA, 'YYYY-MM-DD') AS FECHA_ESTIMADA_ENTREGA
                FROM V_ADMIN_PEDIDOS vp";

        $where = [];
        $params = [];

        if ($estado !== null) {
            $where[] = "vp.ID_ESTADO = :estado";
            $params[':estado'] = $estado;
        }
        if ($fecha_desde !== null && $fecha_desde !== '') {
            $where[] = "vp.FECHA >= TO_DATE(:fecha_desde, 'YYYY-MM-DD')";
            $params[':fecha_desde'] = $fecha_desde;
        }
        if ($fecha_hasta !== null && $fecha_hasta !== '') {
            $where[] = "vp.FECHA < TO_DATE(:fecha_hasta, 'YYYY-MM-DD') + 1";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $query = $base;
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $query .= " ORDER BY vp.ID_PEDIDO DESC";

        $stmt = oci_parse($this->conn, $query);

        foreach ($params as $key => $val) {
            oci_bind_by_name($stmt, $key, $params[$key]);
        }

        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerEstados(): array {
        $query = "SELECT ID_ESTADO, NOMBRE FROM ESTADO_PEDIDO ORDER BY ID_ESTADO";

        $stmt = oci_parse($this->conn, $query);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $results;
    }
}
?>
