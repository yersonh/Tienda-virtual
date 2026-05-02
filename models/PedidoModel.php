<?php

class PedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    public function obtenerPorVenta($idVenta): ?array {
        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        $query = "SELECT ID_PEDIDO,
                         ID_VENTA,
                         ID_ESTADO,
                         FECHA_ESTIMADA_ENTREGA,
                         ID_DIRECCION_PEDIDO
                  FROM PEDIDO
                  WHERE ID_VENTA = :id_venta
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }
}
