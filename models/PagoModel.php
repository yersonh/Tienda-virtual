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

    private function logOracleError($stmt = null, string $context = 'Oracle'): void {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        if (!$error) {
            error_log($context . ': Error de Oracle desconocido');
            return;
        }

        error_log($context . ': ' . json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function procesarPago($idVenta, $idMetodo, ?float $monto = null): void {
        $idVenta = (int) $idVenta;
        $idMetodo = (int) $idMetodo;

        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        if (!in_array($idMetodo, [1, 2, 3, 4], true)) {
            throw new InvalidArgumentException('Metodo de pago invalido');
        }

        if ($monto !== null && round(max(0, $monto), 2) <= 0) {
            throw new InvalidArgumentException('Monto de pago invalido');
        }

        $query = "BEGIN SP_PROCESAR_PAGO(:p_id_venta, :p_metodo); END;";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            $this->logOracleError(null, 'PagoModel::procesarPago oci_parse SP_PROCESAR_PAGO');
            throw new Exception($this->oracleErrorMessage());
        }

        try {
            oci_bind_by_name($stmt, ':p_id_venta', $idVenta, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':p_metodo', $idMetodo, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $this->logOracleError($stmt, 'PagoModel::procesarPago oci_execute SP_PROCESAR_PAGO');
                throw new Exception($this->oracleErrorMessage($stmt));
            }
        } finally {
            oci_free_statement($stmt);
        }
    }

}
