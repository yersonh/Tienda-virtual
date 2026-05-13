<?php

class WompiModel {

    private $conn;
    private array $columnCache    = [];
    private ?array $procesarPagoArgs = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ─────────────────────────────────────────────────────────────────
    //  Punto de entrada principal: registrar una TX de Wompi en BD
    // ─────────────────────────────────────────────────────────────────
    public function registrarTransaccion(array $transaction, string $rawJson): void {
        $idTransaccion = trim((string) ($transaction['id']        ?? ''));
        $referencia    = trim((string) ($transaction['reference'] ?? ''));
        $metodoReal    = $this->metodoReal($transaction);
        $statusWompi   = strtoupper(trim((string) ($transaction['status'] ?? '')));
        $estadoPago    = $this->estadoPagoDesdeWompi($statusWompi);

        error_log("[Wompi Model] registrarTransaccion | TX=$idTransaccion REF=$referencia STATUS=$statusWompi->$estadoPago METODO=$metodoReal");

        if ($idTransaccion === '' || $referencia === '') {
            throw new InvalidArgumentException(
                "[Wompi Model] Transaccion sin id o referencia | id='$idTransaccion' ref='$referencia'"
            );
        }

        $this->validarColumnasWompi();

        $idsReferencia = $this->idsDesdeReferencia($referencia);
        error_log('[Wompi Model] IDs desde referencia: ' . json_encode($idsReferencia));

        // ── Buscar pago existente ─────────────────────────────────────
        $target = $this->buscarPagoExistente($idTransaccion, $referencia);

        if ($target === null) {
            error_log("[Wompi Model] Sin pago existente para TX/REF — buscando por referencia...");
            $target = $this->buscarVentaPorReferencia($referencia, $idsReferencia);
            if ($target !== null) {
                error_log("[Wompi Model] Venta encontrada por referencia: id_venta=" . ($target['id_venta'] ?? 'N/A'));
            } else {
                error_log("[Wompi Model] ERROR CRITICO: sin venta para referencia $referencia");
            }
        } else {
            error_log("[Wompi Model] Pago existente encontrado: id_pago=" . ($target['id_pago'] ?? 'N/A') . " estado=" . ($target['estado'] ?? 'N/A'));
            if (!$this->targetCoincideConReferencia($target, $idsReferencia, $referencia)) {
                error_log("[Wompi Model] Pago existente no coincide con referencia $referencia — resolviendo desde referencia");
                $target = $this->buscarVentaPorReferencia($referencia, $idsReferencia);
            }
        }

        if ($target === null || (int) ($target['id_venta'] ?? 0) <= 0) {
            throw new Exception(
                "[Wompi Model] No se encontro venta para referencia Wompi: $referencia (TX=$idTransaccion)"
            );
        }

        $idVenta = (int) $target['id_venta'];
        $idPago  = (int) ($target['id_pago'] ?? 0);

        if ($idPago <= 0) {
            $idPago = $this->buscarIdPagoPorVenta($idVenta);
        }

        error_log("[Wompi Model] id_venta=$idVenta id_pago=" . ($idPago > 0 ? $idPago : 'NUEVO'));

        // ── Verificar idempotencia ────────────────────────────────────
        $pagoActual = $idPago > 0 ? $this->obtenerPagoPorId($idPago) : null;

        if ($this->pagoYaProcesado($pagoActual, $idTransaccion, $referencia, $estadoPago)) {
            error_log("[Wompi Model] Pago id=$idPago ya procesado como $estadoPago — actualizando solo JSON");
            $this->actualizarJsonPago($idPago, $rawJson);
            return;
        }

        // ── Llamar SP_PROCESAR_PAGO ───────────────────────────────────
        $idMetodo = $this->idMetodoPago($metodoReal);
        error_log("[Wompi Model] Llamando SP_PROCESAR_PAGO | id_venta=$idVenta metodo=$idMetodo estado=$estadoPago tx=$idTransaccion ref=$referencia");

        $this->procesarPagoWompi(
            $idVenta,
            $idMetodo,
            $estadoPago,
            $idTransaccion,
            $referencia,
            $metodoReal,
            $rawJson
        );

        error_log("[Wompi Model] SP_PROCESAR_PAGO OK | TX=$idTransaccion STATUS=$estadoPago id_venta=$idVenta");
    }

    public function registrarTransaccionAprobada(array $transaction, string $rawJson): void {
        $transaction['status'] = 'APPROVED';
        $this->registrarTransaccion($transaction, $rawJson);
    }

    // ─────────────────────────────────────────────────────────────────
    //  Busquedas internas
    // ─────────────────────────────────────────────────────────────────
    private function buscarPagoExistente(string $idTransaccion, string $referencia): ?array {
        $query = "SELECT ID_PAGO, ID_VENTA, ESTADO, ID_TRANSACCION_WOMPI, REFERENCIA_WOMPI
                  FROM   PAGO
                  WHERE  ID_TRANSACCION_WOMPI = :id_transaccion
                      OR REFERENCIA_WOMPI = :referencia
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_transaccion', $idTransaccion);
        oci_bind_by_name($stmt, ':referencia',     $referencia);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function buscarIdPagoPorVenta(int $idVenta): int {
        $query = "SELECT ID_PAGO
                  FROM   PAGO
                  WHERE  ID_VENTA = :id_venta
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
                  FROM   PAGO
                  WHERE  ID_PAGO = :id_pago
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_pago', $idPago, -1, SQLT_INT);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function buscarVentaPorReferencia(string $referencia, ?array $ids = null): ?array {
        $ids ??= $this->idsDesdeReferencia($referencia);

        foreach ($ids['pedidos'] as $idPedido) {
            $porPedido = $this->buscarVentaPorPedido($idPedido);
            if ($porPedido !== null) {
                if (!empty($ids['ventas']) && !in_array((int) ($porPedido['id_venta'] ?? 0), $ids['ventas'], true)) {
                    throw new Exception(
                        "[Wompi Model] Referencia $referencia: la venta del pedido $idPedido no coincide con la venta esperada"
                    );
                }
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

    private function targetCoincideConReferencia(array $target, array $ids, string $referencia): bool {
        $idVentaTarget = (int) ($target['id_venta'] ?? 0);
        if ($idVentaTarget <= 0) {
            return false;
        }

        if (!empty($ids['ventas']) && !in_array($idVentaTarget, $ids['ventas'], true)) {
            return false;
        }

        foreach ($ids['pedidos'] as $idPedido) {
            $ventaPedido = $this->buscarVentaPorPedido($idPedido);
            if ($ventaPedido === null) {
                continue;
            }
            if ((int) ($ventaPedido['id_venta'] ?? 0) !== $idVentaTarget) {
                return false;
            }
        }

        return true;
    }

    private function buscarVentaPorPedido(int $idPedido): ?array {
        $query = "SELECT pe.ID_VENTA
                  FROM   PEDIDO pe
                  WHERE  pe.ID_PEDIDO = :id_pedido
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
                  FROM   VENTA
                  WHERE  ID_VENTA = :id_venta
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($query);
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        $this->execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    // ─────────────────────────────────────────────────────────────────
    //  Escrituras en BD
    // ─────────────────────────────────────────────────────────────────
    private function actualizarJsonPago(int $idPago, string $rawJson): void {
        $query = "UPDATE PAGO
                  SET    JSON_RESPUESTA = :json_respuesta
                  WHERE  ID_PAGO = :id_pago";

        $stmt     = $this->parse($query);
        $jsonClob = oci_new_descriptor($this->conn, OCI_D_LOB);
        $jsonClob->writeTemporary($rawJson, OCI_TEMP_CLOB);

        oci_bind_by_name($stmt, ':id_pago',        $idPago,    -1, SQLT_INT);
        oci_bind_by_name($stmt, ':json_respuesta',  $jsonClob,  -1, SQLT_CLOB);

        try {
            $this->execute($stmt);
            error_log("[Wompi Model] JSON de pago $idPago actualizado");
        } finally {
            $jsonClob->free();
            oci_free_statement($stmt);
        }
    }

    private function procesarPagoWompi(
        int    $idVenta,
        int    $idMetodo,
        string $estadoPago,
        string $idTransaccion,
        string $referencia,
        string $metodoReal,
        string $rawJson
    ): void {
        $sql = "BEGIN SP_PROCESAR_PAGO(
                    :p_id_venta,
                    :p_metodo,
                    :p_estado,
                    :p_transaccion_wompi,
                    :p_referencia_wompi,
                    :p_metodo_real,
                    :p_json_respuesta
                ); END;";

        error_log("[Wompi Model] SP_PROCESAR_PAGO INICIO | id_venta=$idVenta id_metodo=$idMetodo estado='$estadoPago' tx='$idTransaccion' ref='$referencia' metodo_real='$metodoReal' json_len=" . strlen($rawJson));

        $stmt     = $this->parse($sql);
        $jsonClob = null;

        try {
            $jsonClob = oci_new_descriptor($this->conn, OCI_D_LOB);
            $jsonClob->writeTemporary($rawJson, OCI_TEMP_CLOB);

            oci_bind_by_name($stmt, ':p_id_venta',          $idVenta,      -1, SQLT_INT);
            oci_bind_by_name($stmt, ':p_metodo',             $idMetodo,     -1, SQLT_INT);
            oci_bind_by_name($stmt, ':p_estado',             $estadoPago);
            oci_bind_by_name($stmt, ':p_transaccion_wompi',  $idTransaccion);
            oci_bind_by_name($stmt, ':p_referencia_wompi',   $referencia);
            oci_bind_by_name($stmt, ':p_metodo_real',        $metodoReal);
            oci_bind_by_name($stmt, ':p_json_respuesta',     $jsonClob,     -1, SQLT_CLOB);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $ociErr = oci_error($stmt);
                $msg    = is_array($ociErr) ? ($ociErr['message'] ?? 'OCI error desconocido') : 'OCI error desconocido';
                error_log('[Wompi Model] SP_PROCESAR_PAGO ORA ERROR: ' . json_encode($ociErr, JSON_UNESCAPED_UNICODE) . " | id_venta=$idVenta tx='$idTransaccion' ref='$referencia' estado='$estadoPago'");
                throw new Exception('Oracle SP_PROCESAR_PAGO: ' . $msg);
            }

            error_log("[Wompi Model] SP_PROCESAR_PAGO EXITO | id_venta=$idVenta estado='$estadoPago' tx='$idTransaccion'");

        } finally {
            if ($jsonClob) {
                $jsonClob->free();
            }
            oci_free_statement($stmt);
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  Validaciones y helpers
    // ─────────────────────────────────────────────────────────────────
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

        $estadoActual = strtoupper(trim((string) ($pagoActual['estado']               ?? '')));
        $txActual     = trim((string) ($pagoActual['id_transaccion_wompi'] ?? ''));
        $refActual    = trim((string) ($pagoActual['referencia_wompi']     ?? ''));

        // APPROVED con TX real es siempre terminal
        if ($estadoActual === 'APPROVED' && $txActual !== '') {
            error_log("[Wompi Model] pagoYaProcesado: ya APPROVED con TX=$txActual — no reprocesar");
            return true;
        }

        // PAGADO/COMPLETADO con TX real son terminales
        if (in_array($estadoActual, ['PAGADO', 'COMPLETADO'], true) && $txActual !== '') {
            error_log("[Wompi Model] pagoYaProcesado: ya $estadoActual con TX=$txActual — no reprocesar");
            return true;
        }

        $esIdempotente = $estadoActual === $estadoPago
            && ($txActual  === '' || $txActual  === $idTransaccion)
            && ($refActual === '' || $refActual === $referencia);

        if ($esIdempotente) {
            $idPago = (int) ($pagoActual['id_pago']  ?? 0);
            $idVenta = (int) ($pagoActual['id_venta'] ?? 0);
            error_log("[Wompi Model] pagoYaProcesado: OMITIDO (mismo estado) | id_pago=$idPago id_venta=$idVenta estado_actual=$estadoActual estado_entrante=$estadoPago tx_actual='$txActual' tx_entrante='$idTransaccion' ref_actual='$refActual' ref_entrante='$referencia'");
        }

        return $esIdempotente;
    }

    private function validarColumnasWompi(): void {
        $columnas = ['ID_TRANSACCION_WOMPI', 'REFERENCIA_WOMPI', 'METODO_REAL', 'JSON_RESPUESTA'];
        foreach ($columnas as $columna) {
            if (!$this->columnaExiste('PAGO', $columna)) {
                throw new Exception("Falta columna PAGO.$columna — ejecuta sql/wompi_pagos.sql");
            }
        }
    }

    private function columnaExiste(string $tabla, string $columna): bool {
        $key = strtoupper("$tabla.$columna");
        if (array_key_exists($key, $this->columnCache)) {
            return $this->columnCache[$key];
        }

        $query = "SELECT COUNT(*) AS TOTAL
                  FROM   USER_TAB_COLUMNS
                  WHERE  TABLE_NAME  = :tabla
                    AND  COLUMN_NAME = :columna";

        $stmt   = $this->parse($query);
        $tUpper = strtoupper($tabla);
        $cUpper = strtoupper($columna);
        oci_bind_by_name($stmt, ':tabla',   $tUpper);
        oci_bind_by_name($stmt, ':columna', $cUpper);
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
            str_contains($metodoReal, 'DEBIT')    => 2,
            default                               => 3
        };
    }

    private function numerosReferencia(string $referencia): array {
        preg_match_all('/\d+/', $referencia, $matches);
        $numeros = array_map('intval', $matches[0] ?? []);
        return array_values(array_unique(array_filter($numeros, fn($n) => $n > 0)));
    }

    private function idsDesdeReferencia(string $referencia): array {
        $pedidos = [];
        $ventas  = [];

        if (preg_match('/(?:^|[-_])PED[-_]?(\d+)(?:[-_]|$)/i', $referencia, $matches)) {
            $pedidos[] = (int) $matches[1];
        }

        if (preg_match('/(?:^|[-_])VENTA[-_]?(\d+)(?:[-_]|$)/i', $referencia, $matches)) {
            $ventas[] = (int) $matches[1];
        }

        $usados   = array_flip(array_merge($pedidos, $ventas));
        $fallback = array_values(array_filter(
            $this->numerosReferencia($referencia),
            fn($n) => !isset($usados[$n])
        ));

        return [
            'pedidos'  => array_values(array_unique(array_filter($pedidos,  fn($n) => $n > 0))),
            'ventas'   => array_values(array_unique(array_filter($ventas,   fn($n) => $n > 0))),
            'fallback' => $fallback
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    //  Wrappers OCI con logging de errores
    // ─────────────────────────────────────────────────────────────────
    private function parse(string $query) {
        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            $error = oci_error($this->conn);
            $msg   = is_array($error) ? ($error['message'] ?? 'OCI parse error') : 'OCI parse error';
            error_log('[Wompi Model] oci_parse error: ' . json_encode($error) . ' | query=' . substr($query, 0, 200));
            throw new Exception('Oracle parse: ' . $msg);
        }
        return $stmt;
    }

    private function execute($stmt): void {
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            $msg   = is_array($error) ? ($error['message'] ?? 'OCI execute error') : 'OCI execute error';
            error_log('[Wompi Model] oci_execute error: ' . json_encode($error, JSON_UNESCAPED_UNICODE));
            throw new Exception('Oracle execute: ' . $msg);
        }
    }
}
