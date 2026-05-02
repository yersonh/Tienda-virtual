<?php

class VentaModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    public function crearVenta($idUsuario): int {
        $idUsuario = (int) $idUsuario;
        if ($idUsuario <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        $query = "BEGIN PC_CREAR_VENTA(:p_id_usuario, :p_id_venta); END;";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        $idVenta = null;
        oci_bind_by_name($stmt, ':p_id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);

        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new Exception('PC_CREAR_VENTA no retorno un ID_VENTA valido');
        }

        return $idVenta;
    }

    public function obtenerTotalVenta($idVenta): float {
        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        $query = "SELECT NVL(FN_TOTAL_VENTA(:id_venta), 0) AS TOTAL
                  FROM DUAL";

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

        return (float) ($row['TOTAL'] ?? 0);
    }
}
