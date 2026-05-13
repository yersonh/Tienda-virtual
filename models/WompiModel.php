<?php

class WompiModel {

    private $conn;
    private array $columnCache = [];
    private ?array $procesarPagoArgs = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function registrarTransaccion(array $transaction, string $rawJson): void {
        $idTransaccion = trim((string) ($transaction['id'] ?? ''));
        $referencia = trim((string) ($transaction['reference'] ?? ''));
        $metodoReal = $this->metodoReal($transaction);
        $statusWompi = strtoupper(trim((string) ($transaction['status'] ?? '')));
        $estadoPago = $this->estadoPagoDesdeWompi($statusWompi);

        error_log("[Wompi Notification] Processing TX: $idTransaccion, Ref: $referencia, Status: $statusWompi -> Internal: $estadoPago");

        if ($idTransaccion === '' || $referencia === '') {
            throw new InvalidArgumentException('Transaccion Wompi sin id o referencia');
        }

        $this->validarColumnasWompi();

        $target = $this->buscarPagoExistente($idTransaccion, $referencia);
        if ($target === null) {
            $target = $this->buscarVentaPorReferencia($referencia);
            if ($target) {
                error_log("[Wompi Notification] Found target by reference: " . ($target['id_venta'] ?? 'N/A'));
            }
        } else {
            error_log("[Wompi Notification] Found existing payment for TX/Ref: " . ($target['id_pago'] ?? 'N/A'));
        }

        if ($target === null || (int) ($target['id_venta'] ?? 0) <= 0) {
            error_log("[Wompi Notification] ERROR: No target found for reference $referencia");
            throw new Exception('No se encontro una venta o pedido asociado a la referencia Wompi: ' . $referencia);
        }

        $idVenta = (int) $target['id_venta'];
        $idPago = (int) ($target['id_pago'] ?? 0);
        if ($idPago <= 0) {
            $idPago = $this->buscarIdPagoPorVenta($idVenta);
        }

        $pagoActual = $idPago > 0 ? $this->obtenerPagoPorId($idPago) : null;
        if ($this->pagoYaProcesado($pagoActual, $idTransaccion, $referencia, $estadoPago)) {
            error_log("[Wompi Notification] Payment $idPago already processed as $estadoPago. Updating JSON only.");
            $this->actualizarJsonPago($idPago, $rawJson);
            return;
        }

        error_log("[Wompi Notification] Calling SP_PROCESAR_PAGO for Venta $idVenta, Status $estadoPago");
        $this->procesarPagoWompi(
            $idVenta,
            $this->idMetodoPago($metodoReal),
            $estadoPago,
            $idTransaccion,
            $referencia,
            $metodoReal,
            $rawJson
        );

        error_log("[Wompi Notification] TX $idTransaccion processed successfully.");
    }

    public function registrarTransaccionAprobada(array $transaction, string $rawJson): void {
        $transaction['status'] = 'APPROVED';
        $this->registrarTransaccion($transaction, $rawJson);
    }

    private function buscarPagoExistente(string $idTransaccion, string $referencia): ?array {
        $query = "SELECT ID_PAGO, ID_VENTA, ESTADO, ID_TRANSACCION_WOMPI, REFERENCIA_WOMPI
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

    private function obtenerPagoPorId(int $idPago): ?array {
        $query = "SELECT ID_PAGO, ID_VENTA, ESTADO, ID_TRANSACCION_WOMPI, REFERENCIA_WOMPI
                  FROM PAGO
                  WHERE ID_PAGO = :id_pago
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_pago', $idPago, -1, SQLT_INT);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
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

    private function actualizarJsonPago(int $idPago, string $rawJson): void {
        $query = "UPDATE PAGO
                  SET JSON_RESPUESTA = :json_respuesta
                  WHERE ID_PAGO = :id_pago";

        $stmt = $this->parse($query);
        $jsonClob = oci_new_descriptor($this->conn, OCI_D_LOB);
        $jsonClob->writeTemporary($rawJson, OCI_TEMP_CLOB);

        oci_bind_by_name($stmt, ':id_pago', $idPago, -1, SQLT_INT);
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

    private function procesarPagoWompi(
        int $idVenta,
        int $idMetodo,
        string $estadoPago,
        string $idTransaccion,
        string $referencia,
        string $metodoReal,
        string $rawJson
    ): void {
        $stmt = $this->parse(
            "BEGIN SP_PROCESAR_PAGO(
                :p_id_venta,
                :p_metodo,
                :p_estado,
                :p_transaccion_wompi,
                :p_referencia_wompi,
                :p_metodo_real,
                :p_json_respuesta
            ); END;"
        );
        $jsonClob = null;

        try {
            $jsonClob = oci_new_descriptor($this->conn, OCI_D_LOB);
            $jsonClob->writeTemporary($rawJson, OCI_TEMP_CLOB);

            oci_bind_by_name($stmt, ':p_id_venta', $idVenta, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':p_metodo', $idMetodo, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':p_estado', $estadoPago);
            oci_bind_by_name($stmt, ':p_transaccion_wompi', $idTransaccion);
            oci_bind_by_name($stmt, ':p_referencia_wompi', $referencia);
            oci_bind_by_name($stmt, ':p_metodo_real', $metodoReal);
            oci_bind_by_name($stmt, ':p_json_respuesta', $jsonClob, -1, SQLT_CLOB);

            $this->execute($stmt);
        } finally {
            if ($jsonClob) {
                $jsonClob->free();
            }
            oci_free_statement($stmt);
        }
    }

    private function argumentosProcedimiento(string $procedimiento): array {
        if ($procedimiento === 'SP_PROCESAR_PAGO' && $this->procesarPagoArgs !== null) {
            return $this->procesarPagoArgs;
        }

        $query = "SELECT ARGUMENT_NAME,
                         POSITION,
                         IN_OUT,
                         DATA_TYPE
                  FROM USER_ARGUMENTS
                  WHERE OBJECT_NAME = :procedimiento
                    AND PACKAGE_NAME IS NULL
                  ORDER BY POSITION";

        $stmt = $this->parse($query);
        $procedimiento = strtoupper($procedimiento);
        oci_bind_by_name($stmt, ':procedimiento', $procedimiento);
        $this->execute($stmt);

        $argumentos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            if (empty($row['ARGUMENT_NAME'])) {
                continue;
            }

            $argumentos[] = [
                'name' => strtoupper((string) $row['ARGUMENT_NAME']),
                'position' => (int) ($row['POSITION'] ?? 0),
                'in_out' => strtoupper((string) ($row['IN_OUT'] ?? 'IN')),
                'data_type' => strtoupper((string) ($row['DATA_TYPE'] ?? ''))
            ];
        }
        oci_free_statement($stmt);

        if ($procedimiento === 'SP_PROCESAR_PAGO') {
            $this->procesarPagoArgs = $argumentos;
        }

        return $argumentos;
    }

    private function procedimientoAcepta(array $argumentos, array $tokens): bool {
        foreach ($argumentos as $argumento) {
            foreach ($tokens as $token) {
                if (str_contains($argumento, $token)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function estadoPagoDesdeWompi(string $statusWompi): string {
        return match ($statusWompi) {
            'APPROVED', 'DECLINED', 'ERROR', 'VOIDED' => $statusWompi,
            default => $statusWompi !== '' ? $statusWompi : 'PENDIENTE'
        };
    }

    private function pagoYaProcesado(?array $pagoActual, string $idTransaccion, string $referencia, string $estadoPago): bool {
        if (!$pagoActual) {
            return false;
        }

        $estadoActual = strtoupper(trim((string) ($pagoActual['estado'] ?? '')));
        $txActual = trim((string) ($pagoActual['id_transaccion_wompi'] ?? ''));
        $refActual = trim((string) ($pagoActual['referencia_wompi'] ?? ''));

        // APPROVED siempre es terminal — nunca sobreescribir.
        if ($estadoActual === 'APPROVED') {
            return true;
        }

        // PAGADO/COMPLETADO solo son terminales cuando ya tienen un TX Wompi real.
        // Sin TX_ID son placeholders de SP_CREAR_PEDIDO_COMPLETO que deben ser
        // procesados por el webhook para guardar el ID de transacción correcto.
        if (in_array($estadoActual, ['PAGADO', 'COMPLETADO'], true) && $txActual !== '') {
            return true;
        }

        return $estadoActual === $estadoPago
            && ($txActual === '' || $txActual === $idTransaccion)
            && ($refActual === '' || $refActual === $referencia);
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
