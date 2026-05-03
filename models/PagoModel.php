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
        $monto = $monto !== null ? round(max(0, $monto), 2) : 0;

        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        if (!in_array($idMetodo, [1, 2, 3, 4], true)) {
            throw new InvalidArgumentException('Metodo de pago invalido');
        }

        if ($monto <= 0) {
            throw new InvalidArgumentException('Monto de pago invalido');
        }

        $estadoPago = 'PAGADO';
        $montoPago = $this->numeroOracle($monto);

        $query = "INSERT INTO PAGO (
                    ID_PAGO,
                    ID_VENTA,
                    ID_METODO,
                    MONTO,
                    FECHA,
                    ESTADO
                  )
                  VALUES (
                    SEQ_PAGO.NEXTVAL,
                    :id_venta,
                    :id_metodo,
                    :monto,
                    SYSTIMESTAMP,
                    :estado
                  )";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        try {
            oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_metodo', $idMetodo, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':monto', $montoPago, -1, SQLT_CHR);
            oci_bind_by_name($stmt, ':estado', $estadoPago);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }
        } finally {
            oci_free_statement($stmt);
        }

        $this->actualizarVentaPagadaTx($idVenta, $idMetodo);
        $this->actualizarPedidoPagadoTx($idVenta);
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

    private function actualizarPedidoPagadoTx(int $idVenta): void {
        $query = "UPDATE PEDIDO
                  SET ID_ESTADO = 2
                  WHERE ID_VENTA = :id_venta
                  AND ID_ESTADO = 1";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
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
