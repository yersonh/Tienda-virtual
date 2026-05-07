<?php

class PagoModel {

    private $conn;
    private $columnasCache = [];

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

    private function columnasTabla(string $tabla): array {
        $tabla = strtoupper($tabla);
        if (isset($this->columnasCache[$tabla])) {
            return $this->columnasCache[$tabla];
        }

        $query = "SELECT COLUMN_NAME
                  FROM USER_TAB_COLUMNS
                  WHERE TABLE_NAME = :tabla";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':tabla', $tabla);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $columnas = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $columnas[strtolower($row['COLUMN_NAME'])] = $row['COLUMN_NAME'];
        }
        oci_free_statement($stmt);

        $this->columnasCache[$tabla] = $columnas;
        return $columnas;
    }

    private function columnaExiste(string $tabla, string $columna): bool {
        $columnas = $this->columnasTabla($tabla);
        return isset($columnas[strtolower($columna)]);
    }

    private function numeroOracle(float $valor): string {
        return number_format($valor, 2, '.', '');
    }

    private function nombreMetodoPago(int $metodo): string {
        return match ($metodo) {
            1 => 'Efectivo',
            2 => 'Tarjeta debito',
            3 => 'Tarjeta credito',
            4 => 'Transferencia bancaria',
            default => 'Registrado'
        };
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

    private function actualizarVentaPagadaTx(int $idVenta, int $idMetodo): void {
        $sets = [];
        $bindMetodo = false;
        $estadoVenta = 'PAGADA';
        $metodoPago = $this->nombreMetodoPago($idMetodo);

        if ($this->columnaExiste('VENTA', 'ESTADO')) {
            $sets[] = 'ESTADO = :estado_venta';
        }
        if ($this->columnaExiste('VENTA', 'METODO_PAGO')) {
            $sets[] = 'METODO_PAGO = :metodo_pago';
            $bindMetodo = true;
        }

        if (empty($sets)) {
            return;
        }

        $query = "UPDATE VENTA
                  SET " . implode(', ', $sets) . "
                  WHERE ID_VENTA = :id_venta";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        if ($this->columnaExiste('VENTA', 'ESTADO')) {
            oci_bind_by_name($stmt, ':estado_venta', $estadoVenta);
        }
        if ($bindMetodo) {
            oci_bind_by_name($stmt, ':metodo_pago', $metodoPago);
        }
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
    }

}
