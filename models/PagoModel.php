<?php

class PagoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    public function procesarPago($idVenta, $idMetodo): void {
        $idVenta = (int) $idVenta;
        $idMetodo = (int) $idMetodo;

        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        if ($idMetodo <= 0) {
            throw new InvalidArgumentException('Metodo de pago invalido');
        }

        $query = "BEGIN SP_PROCESAR_PAGO(:p_id_venta, :p_id_metodo); END;";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':p_id_venta', $idVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_id_metodo', $idMetodo, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
    }
}
