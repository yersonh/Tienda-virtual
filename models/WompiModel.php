<?php

class WompiModel {

    private $conn;
    private array $columnCache = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function registrarTransaccionAprobada(array $transaction, string $rawJson): void {
        $idTransaccion = trim((string) ($transaction['id'] ?? ''));
        $referencia = trim((string) ($transaction['reference'] ?? ''));
        $metodoReal = $this->metodoReal($transaction);

        if ($idTransaccion === '' || $referencia === '') {
            throw new InvalidArgumentException('Transaccion Wompi sin id o referencia');
        }

        $this->validarColumnasWompi();

        $target = $this->buscarPagoExistente($idTransaccion, $referencia);
        if ($target === null) {
            $target = $this->buscarVentaPorReferencia($referencia);
        }

        if ($target === null || (int) ($target['id_venta'] ?? 0) <= 0) {
            throw new Exception('No se encontro una venta o pedido asociado a la referencia Wompi: ' . $referencia);
        }

        $idVenta = (int) $target['id_venta'];
        $idPago = (int) ($target['id_pago'] ?? 0);
        if ($idPago <= 0) {
            $idPago = $this->buscarIdPagoPorVenta($idVenta);
        }

        if ($idPago > 0) {
            $this->actualizarPagoWompi($idPago, $idTransaccion, $referencia, $metodoReal, $rawJson);
        } else {
            $this->insertarPagoWompi($idVenta, $this->idMetodoPago($metodoReal), $idTransaccion, $referencia, $metodoReal, $rawJson);
        }

        oci_commit($this->conn);
    }

    private function buscarPagoExistente(string $idTransaccion, string $referencia): ?array {
        $query = "SELECT ID_PAGO, ID_VENTA
                  FROM PAGO
                  WHERE ID_TRANSACCION_WOMPI = :id_transaccion
                     OR REFERENCIA_WOMPI = :referencia
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_transaccion', $idTransaccion);
        oci_bind_by_name($stmt, ':referencia', $referencia);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function buscarIdPagoPorVenta(int $idVenta): int {
        $query = "SELECT ID_PAGO
                  FROM PAGO
                  WHERE ID_VENTA = :id_venta
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['ID_PAGO'] ?? 0);
    }

    private function buscarVentaPorReferencia(string $referencia): ?array {
        $ids = $this->idsDesdeReferencia($referencia);

        foreach ($ids['pedidos'] as $idPedido) {
            $porPedido = $this->buscarVentaPorPedido($idPedido);
            if ($porPedido !== null) {
                return $porPedido;
            }
        }

        foreach ($ids['ventas'] as $idVenta) {
            $porVenta = $this->buscarVentaPorId($idVenta);
            if ($porVenta !== null) {
                return $porVenta;
            }
        }

        foreach ($ids['fallback'] as $numero) {
            $porPedido = $this->buscarVentaPorPedido($numero);
            if ($porPedido !== null) {
                return $porPedido;
            }

            $porVenta = $this->buscarVentaPorId($numero);
            if ($porVenta !== null) {
                return $porVenta;
            }
        }

        return null;
    }

    private function buscarVentaPorPedido(int $idPedido): ?array {
        $query = "SELECT pe.ID_VENTA
                  FROM PEDIDO pe
                  WHERE pe.ID_PEDIDO = :id_pedido
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function buscarVentaPorId(int $idVenta): ?array {
        $query = "SELECT ID_VENTA
                  FROM VENTA
                  WHERE ID_VENTA = :id_venta
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function insertarPagoWompi(int $idVenta, int $idMetodo, string $idTransaccion, string $referencia, string $metodoReal, string $rawJson): void {
        $monto = $this->totalVenta($idVenta);
        $query = "INSERT INTO PAGO (
                      ID_PAGO,
                      ID_VENTA,
                      ID_METODO,
                      MONTO,
                      FECHA,
                      ESTADO,
                      ID_TRANSACCION_WOMPI,
                      REFERENCIA_WOMPI,
                      METODO_REAL,
                      JSON_RESPUESTA
                  ) VALUES (
                      SEQ_PAGO.NEXTVAL,
                      :id_venta,
                      :id_metodo,
                      :monto,
                      SYSTIMESTAMP,
                      'PAGADO',
                      :id_transaccion,
                      :referencia,
                      :metodo_real,
                      :json_respuesta
                  )";

        $stmt = $this->parse($query);
        $jsonClob = oci_new_descriptor($this->conn, OCI_D_LOB);
        $jsonClob->writeTemporary($rawJson, OCI_TEMP_CLOB);

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_metodo', $idMetodo, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':monto', $monto);
        oci_bind_by_name($stmt, ':id_transaccion', $idTransaccion);
        oci_bind_by_name($stmt, ':referencia', $referencia);
        oci_bind_by_name($stmt, ':metodo_real', $metodoReal);
        oci_bind_by_name($stmt, ':json_respuesta', $jsonClob, -1, SQLT_CLOB);

        try {
            $this->execute($stmt);
        } finally {
            $jsonClob->free();
            oci_free_statement($stmt);
        }
    }

    private function actualizarPagoWompi(int $idPago, string $idTransaccion, string $referencia, string $metodoReal, string $rawJson): void {
        $query = "UPDATE PAGO
                  SET ESTADO = 'PAGADO',
                      ID_TRANSACCION_WOMPI = :id_transaccion,
                      REFERENCIA_WOMPI = :referencia,
                      METODO_REAL = :metodo_real,
                      JSON_RESPUESTA = :json_respuesta
                  WHERE ID_PAGO = :id_pago";

        $stmt = $this->parse($query);
        $jsonClob = oci_new_descriptor($this->conn, OCI_D_LOB);
        $jsonClob->writeTemporary($rawJson, OCI_TEMP_CLOB);

        oci_bind_by_name($stmt, ':id_pago', $idPago, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_transaccion', $idTransaccion);
        oci_bind_by_name($stmt, ':referencia', $referencia);
        oci_bind_by_name($stmt, ':metodo_real', $metodoReal);
        oci_bind_by_name($stmt, ':json_respuesta', $jsonClob, -1, SQLT_CLOB);

        try {
            $this->execute($stmt);
        } finally {
            $jsonClob->free();
            oci_free_statement($stmt);
        }
    }

    private function totalVenta(int $idVenta): string {
        $query = "SELECT NVL(TOTAL, 0) AS TOTAL
                  FROM VENTA
                  WHERE ID_VENTA = :id_venta
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return number_format((float) ($row['TOTAL'] ?? 0), 2, '.', '');
    }

    private function validarColumnasWompi(): void {
        $columnas = [
            'ID_TRANSACCION_WOMPI',
            'REFERENCIA_WOMPI',
            'METODO_REAL',
            'JSON_RESPUESTA'
        ];

        foreach ($columnas as $columna) {
            if (!$this->columnaExiste('PAGO', $columna)) {
                throw new Exception('Falta columna PAGO.' . $columna . '. Ejecuta sql/wompi_pagos.sql');
            }
        }
    }

    private function columnaExiste(string $tabla, string $columna): bool {
        $key = strtoupper($tabla . '.' . $columna);
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $query = "SELECT COUNT(*) AS TOTAL
                  FROM USER_TAB_COLUMNS
                  WHERE TABLE_NAME = :tabla
                    AND COLUMN_NAME = :columna";

        $stmt = $this->parse($query);
        $tabla = strtoupper($tabla);
        $columna = strtoupper($columna);
        oci_bind_by_name($stmt, ':tabla', $tabla);
        oci_bind_by_name($stmt, ':columna', $columna);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        $this->columnCache[$key] = ((int) ($row['TOTAL'] ?? 0)) > 0;
        return $this->columnCache[$key];
    }

    private function metodoReal(array $transaction): string {
        $method = $transaction['payment_method'] ?? [];
        if (is_array($method)) {
            foreach (['type', 'payment_method_type', 'name'] as $key) {
                if (!empty($method[$key])) {
                    return strtoupper(trim((string) $method[$key]));
                }
            }
        }

        return strtoupper(trim((string) ($transaction['payment_method_type'] ?? 'WOMPI')));
    }

    private function idMetodoPago(string $metodoReal): int {
        return match (true) {
            str_contains($metodoReal, 'PSE'),
            str_contains($metodoReal, 'BANCOLOMBIA'),
            str_contains($metodoReal, 'TRANSFER') => 4,
            str_contains($metodoReal, 'DEBIT') => 2,
            default => 3
        };
    }

    private function numerosReferencia(string $referencia): array {
        preg_match_all('/\d+/', $referencia, $matches);
        $numeros = array_map('intval', $matches[0] ?? []);
        return array_values(array_unique(array_filter($numeros, fn($numero) => $numero > 0)));
    }

    private function idsDesdeReferencia(string $referencia): array {
        $pedidos = [];
        $ventas = [];

        if (preg_match('/(?:^|[-_])PED[-_]?(\d+)(?:[-_]|$)/i', $referencia, $matches)) {
            $pedidos[] = (int) $matches[1];
        }

        if (preg_match('/(?:^|[-_])VENTA[-_]?(\d+)(?:[-_]|$)/i', $referencia, $matches)) {
            $ventas[] = (int) $matches[1];
        }

        $usados = array_flip(array_merge($pedidos, $ventas));
        $fallback = array_values(array_filter(
            $this->numerosReferencia($referencia),
            fn($numero) => !isset($usados[$numero])
        ));

        return [
            'pedidos' => array_values(array_unique(array_filter($pedidos, fn($numero) => $numero > 0))),
            'ventas' => array_values(array_unique(array_filter($ventas, fn($numero) => $numero > 0))),
            'fallback' => $fallback
        ];
    }

    private function parse(string $query) {
        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            $error = oci_error($this->conn);
            throw new Exception($error['message'] ?? 'Error de Oracle al preparar consulta');
        }
        return $stmt;
    }

    private function execute($stmt): void {
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            throw new Exception($error['message'] ?? 'Error de Oracle al ejecutar consulta');
        }
    }
}
