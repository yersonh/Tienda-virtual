<?php
class AdminPedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerTodos(?int $estado = null, ?string $fecha_desde = null, ?string $fecha_hasta = null): array {
        $base = "SELECT p.ID_PEDIDO,
                       p.ID_ESTADO,
                       ep.NOMBRE AS ESTADO_NOMBRE,
                       TO_CHAR(v.FECHA, 'YYYY-MM-DD HH24:MI:SS') AS FECHA,
                       NVL(v.TOTAL, 0) AS TOTAL,
                       NVL(v.IVA, 0) AS IVA,
                       NVL(v.ENVIO, 0) AS ENVIO,
                       per.NOMBRES AS CLIENTE_NOMBRE,
                       per.APELLIDOS AS CLIENTE_APELLIDO,
                       dp.NOMBRE_RECEPTOR,
                       dp.APELLIDO_RECEPTOR,
                       dp.DIRECCION_ENVIO,
                       dp.CIUDAD,
                       dp.BARRIO,
                       dp.TELEFONO_RECEPTOR,
                       dp.INFORMACION_ADICIONAL,
                       TO_CHAR(p.FECHA_ESTIMADA_ENTREGA, 'YYYY-MM-DD') AS FECHA_ESTIMADA_ENTREGA
                FROM PEDIDO p
                INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                LEFT JOIN ESTADO_PEDIDO ep ON ep.ID_ESTADO = p.ID_ESTADO
                LEFT JOIN USUARIO u ON u.ID_USUARIO = v.ID_USUARIO
                LEFT JOIN PERSONA per ON per.ID_PERSONA = u.ID_PERSONA
                LEFT JOIN DIRECCION_PEDIDO dp ON dp.ID_DIRECCION_PEDIDO = p.ID_DIRECCION_PEDIDO";

        $where = [];
        $params = [];

        if ($estado !== null) {
            $where[] = "p.ID_ESTADO = :estado";
            $params[':estado'] = $estado;
        }
        if ($fecha_desde !== null && $fecha_desde !== '') {
            $where[] = "TRUNC(v.FECHA) >= TO_DATE(:fecha_desde, 'YYYY-MM-DD')";
            $params[':fecha_desde'] = $fecha_desde;
        }
        if ($fecha_hasta !== null && $fecha_hasta !== '') {
            $where[] = "TRUNC(v.FECHA) <= TO_DATE(:fecha_hasta, 'YYYY-MM-DD')";
            $params[':fecha_hasta'] = $fecha_hasta;
        }

        $query = $base;
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }
        $query .= " ORDER BY p.ID_PEDIDO DESC";

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
