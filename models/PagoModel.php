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
        throw new RuntimeException('El pago simulado esta deshabilitado. SP_PROCESAR_PAGO solo debe ejecutarse desde el webhook Wompi.');
    }

}
