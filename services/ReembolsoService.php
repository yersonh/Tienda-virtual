<?php
require_once __DIR__ . '/../models/DevolucionModel.php';

class ReembolsoService {

    private $conn;
    private DevolucionModel $model;

    // Wompi sólo garantiza API refund para pagos con tarjeta
    private const METODOS_CON_REFUND_API = ['CARD', 'CREDIT_CARD', 'DEBIT_CARD'];

    public function __construct($conn, DevolucionModel $model) {
        $this->conn  = $conn;
        $this->model = $model;
    }

    // ─── WOMPI API ────────────────────────────────────────────────────────────

    /**
     * POST /v1/transactions/{id}/refund
     *
     * Retorna:
     *   ['ok' => true,  'id_refund' => string]
     *   ['ok' => false, 'error' => string (legible), 'raw' => string (para BD)]
     */
    private function callWompi(string $transactionId, int $amountInCents): array {
        $privateKey = trim((string) getenv('WOMPI_PRIVATE_KEY'));
        if ($privateKey === '') {
            error_log('[ReembolsoService] ABORT: WOMPI_PRIVATE_KEY no configurada');
            return ['ok' => false, 'error' => 'WOMPI_PRIVATE_KEY no configurada', 'raw' => 'SIN_KEY'];
        }

        $isSandbox = str_starts_with($privateKey, 'prv_test_');
        $baseUrl = $isSandbox
        ? 'https://sandbox.wompi.co/v1'
        : 'https://api.wompi.co/v1';

        $url     = $baseUrl . '/transactions/' . rawurlencode($transactionId) . '/refund';
        $payload = ['amount_in_cents' => $amountInCents];
        $body    = (string) json_encode($payload);

        // ── Log REQUEST completo ────────────────────────────────────────────
        error_log('[ReembolsoService] ══════════════ REQUEST ══════════════════');
        error_log('[ReembolsoService] método          : POST');
        error_log('[ReembolsoService] endpoint        : ' . $url);
        error_log('[ReembolsoService] transaction_id  : ' . $transactionId);
        error_log('[ReembolsoService] amount_in_cents : ' . $amountInCents . '  →  ' . ($amountInCents / 100) . ' COP');
        error_log('[ReembolsoService] payload_json    : ' . $body);
        error_log('[ReembolsoService] entorno         : ' . ($isSandbox ? 'SANDBOX (sandbox.wompi.co)' : 'PRODUCCION (api.wompi.co)'));
        error_log('[ReembolsoService] key_prefix      : ' . substr($privateKey, 0, 16) . '...');

        // Advertencia monto mínimo antes de enviar
        if ($amountInCents < 30000) {
            error_log('[ReembolsoService] ADVERTENCIA monto: amount_in_cents=' . $amountInCents
                . ' (' . ($amountInCents / 100) . ' COP) – Wompi suele rechazar refunds < 300 COP');
        }

        $ch = curl_init($url);
        if (!$ch) {
            error_log('[ReembolsoService] ERROR: curl_init() devolvió false');
            return ['ok' => false, 'error' => 'No se pudo inicializar cURL', 'raw' => 'CURL_INIT_FAIL'];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $privateKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ]);

        $responseBody = curl_exec($ch);
        $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        curl_close($ch);

        $rawStr = $responseBody === false ? '' : (string) $responseBody;

        // ── Log RESPONSE completo ───────────────────────────────────────────
        error_log('[ReembolsoService] ══════════════ RESPONSE ═════════════════');
        error_log('[ReembolsoService] http_code    : ' . ($httpCode === 0 ? '0 (sin respuesta)' : (string) $httpCode));
        error_log('[ReembolsoService] curl_error   : ' . ($curlError !== '' ? $curlError : '(ninguno)'));
        error_log('[ReembolsoService] body_raw     : ' . ($responseBody === false ? '(false – fallo cURL antes de conectar)' : $rawStr));

        // cURL falló antes de conectar
        if ($responseBody === false) {
            $msg = 'cURL falló: ' . $curlError;
            $raw = 'HTTP 0 | CURL_ERROR: ' . $curlError;
            error_log('[ReembolsoService] RESULT: FAIL – ' . $msg);
            return ['ok' => false, 'error' => $msg, 'raw' => mb_substr($raw, 0, 480)];
        }

        $parsed = json_decode($rawStr, true);

        // HTTP 2xx = éxito
        if ($httpCode >= 200 && $httpCode < 300) {
            $refundId = (string) ($parsed['data']['id'] ?? $parsed['id'] ?? $transactionId . '-REFUND');
            error_log('[ReembolsoService] RESULT: OK – refund_id=' . $refundId);
            return ['ok' => true, 'id_refund' => $refundId];
        }

        // Error: extraer mensaje en los distintos formatos que usa Wompi
        $errType     = (string) ($parsed['error']['type']     ?? '');
        $errReason   = (string) ($parsed['error']['reason']   ?? '');
        $errMsgInner = (string) ($parsed['error']['message']  ?? '');
        $errMsgTop   = (string) ($parsed['message']           ?? '');
        $errMessages = (array)  ($parsed['error']['messages'] ?? []);
        $firstMsg    = !empty($errMessages) ? (string) reset($errMessages) : '';

        $human     = $errReason ?: $errMsgInner ?: $firstMsg ?: $errMsgTop ?: 'sin_mensaje';
        $typeLabel = $errType !== '' ? '[' . $errType . '] ' : '';
        $humanFull = 'HTTP ' . $httpCode . ': ' . $typeLabel . $human;

        error_log('[ReembolsoService] RESULT: FAIL – ' . $humanFull);
        error_log('[ReembolsoService] error_block  : ' . json_encode($parsed['error'] ?? $parsed));

        // raw para BD: "HTTP 422 | {body completo truncado}" – más diagnóstico posible
        $rawForDb = 'HTTP ' . $httpCode . ' | ' . mb_substr($rawStr, 0, 440);

        return [
            'ok'    => false,
            'error' => mb_substr($humanFull, 0, 300),
            'raw'   => mb_substr($rawForDb, 0, 480),
        ];
    }

    // ─── FLUJO PRINCIPAL ──────────────────────────────────────────────────────

    /**
     * Ejecuta el reembolso Wompi y registra el resultado en DEVOLUCION.
     * NO lanza excepción si Wompi falla: guarda PENDIENTE-MANUAL para revisión manual.
     *
     * @return string  'REALIZADO' | 'PENDIENTE-MANUAL' | 'SIN-TRANSACCION'
     */
    public function procesarReembolso(int $idDevolucion, array $detalle): string {
        $cantidadAprobada = max(1, (int)   ($detalle['cantidad_aprobada'] ?? 1));
        $idDetalle        = (int)           ($detalle['id_detalle']       ?? 0);
        $precioUnitario   = (float)         ($detalle['precio_unitario']  ?? 0);

        if ($precioUnitario <= 0 && $idDetalle > 0) {
            $precioUnitario = $this->model->obtenerPrecioUnitarioDetalle($idDetalle);
        }

        $testMode = strtolower(trim((string) getenv('WOMPI_TEST_MODE')));
        $isTest   = in_array($testMode, ['1', 'true', 'yes', 'on'], true);

        // Monto real del ítem devuelto (COP → cents para la API)
        $montoReembolsoCOP = max(300, (int) round($precioUnitario * $cantidadAprobada));
        // En sandbox Wompi no procesa reembolsos reales; usamos 1500 COP o el monto real si es menor
        if ($isTest) {
            $montoReembolsoCOP = min($montoReembolsoCOP, 1500);
        }
        $amountInCents = $montoReembolsoCOP * 100;

        // ── Obtener devolución y transacción Wompi aprobada ─────────────────
        $devolucion  = $this->model->obtenerDevolucionPorId($idDevolucion);
        $transaccion = $devolucion
            ? $this->model->obtenerTransaccionWompiPorPedido((int) $devolucion['id_pedido'])
            : null;

        $wompiTxId  = (string) ($transaccion['id_transaccion_wompi'] ?? '');
        $referencia = (string) ($transaccion['referencia_wompi']     ?? '');
        $totalVenta = (float)  ($transaccion['total']                ?? 0);
        $metodoPago = (string) ($transaccion['metodo_real']          ?? 'DESCONOCIDO');

        // ── Log diagnóstico completo ────────────────────────────────────────
        error_log('[ReembolsoService] ══════════ procesarReembolso ══════════');
        error_log('[ReembolsoService] id_devolucion     : ' . $idDevolucion);
        error_log('[ReembolsoService] id_pedido         : ' . ($devolucion['id_pedido'] ?? 'NULL'));
        error_log('[ReembolsoService] id_detalle        : ' . $idDetalle);
        error_log('[ReembolsoService] precio_unitario   : ' . $precioUnitario . ' COP');
        error_log('[ReembolsoService] cantidad_aprobada : ' . $cantidadAprobada);
        error_log('[ReembolsoService] total_venta       : ' . $totalVenta . ' COP');
        error_log('[ReembolsoService] monto_refund_cop  : ' . $montoReembolsoCOP . ' COP');
        error_log('[ReembolsoService] amount_in_cents   : ' . $amountInCents . '  →  ' . ($amountInCents / 100) . ' COP');
        error_log('[ReembolsoService] is_test_mode      : ' . ($isTest ? 'SI' : 'NO'));
        error_log('[ReembolsoService] wompi_tx_id       : ' . ($wompiTxId !== '' ? $wompiTxId : '(vacío)'));
        error_log('[ReembolsoService] referencia_wompi  : ' . ($referencia !== '' ? $referencia : '(vacía)'));
        error_log('[ReembolsoService] metodo_pago_real  : ' . $metodoPago);
        error_log('[ReembolsoService] transaccion_row   : ' . json_encode($transaccion));

        // ── Advertencias de diagnóstico ─────────────────────────────────────

        // Monto demasiado bajo para producción
        if (!$isTest && $amountInCents < 30000) {
            error_log('[ReembolsoService] DIAGNÓSTICO monto: amount_in_cents='
                . $amountInCents . ' (' . ($amountInCents / 100) . ' COP) es MUY BAJO.'
                . ' Wompi suele rechazar refunds < 300 COP. El precio real del ítem es '
                . $precioUnitario . ' COP (× ' . $cantidadAprobada . ' = '
                . ($precioUnitario * $cantidadAprobada) . ' COP).');
        }

        // Refund parcial vs total pagado
        if ($totalVenta > 0 && $montoReembolsoCOP < $totalVenta) {
            error_log('[ReembolsoService] DIAGNÓSTICO monto: refund PARCIAL ('
                . $montoReembolsoCOP . ' COP de ' . $totalVenta . ' COP total pagado).'
                . ' Algunos métodos de pago no permiten refunds parciales.');
        }

        // Método de pago sin soporte de API refund
        $soportaRefundApi = array_reduce(
            self::METODOS_CON_REFUND_API,
            fn(bool $c, string $m) => $c || str_contains(strtoupper($metodoPago), $m),
            false
        );
        if ($metodoPago !== 'DESCONOCIDO' && !$soportaRefundApi) {
            error_log('[ReembolsoService] DIAGNÓSTICO método: metodo_pago_real="' . $metodoPago
                . '" puede NO soportar refunds vía API de Wompi.'
                . ' API refund funciona principalmente para CARD.'
                . ' PSE / Nequi / BANCOLOMBIA_TRANSFER generalmente requieren proceso manual en dashboard.');
        }

        // ── Validar existencia de transacción ───────────────────────────────
        if (!$transaccion || $wompiTxId === '') {
            $errMsg = 'Sin transacción Wompi APPROVED para pedido #'
                . ($devolucion['id_pedido'] ?? '?')
                . '. Verificar PAGO.ESTADO=\'APPROVED\' y PAGO.ID_TRANSACCION_WOMPI no vacío.';
            error_log('[ReembolsoService] ABORT: ' . $errMsg);
            $this->model->registrarReembolso($idDevolucion, 'SIN-TRANSACCION', '', mb_substr($errMsg, 0, 480));
            return 'SIN-TRANSACCION';
        }

        // ── Validar formato del transaction ID ─────────────────────────────
        // Wompi TX IDs: UUID  o  formato numérico  51494-1723814400-48877
        $esUuid  = (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $wompiTxId);
        $esNumId = (bool) preg_match('/^\d{4,}-\d{8,}-\d+$/', $wompiTxId);

        if ($esUuid || $esNumId) {
            error_log('[ReembolsoService] TX ID formato: ' . ($esUuid ? 'UUID' : 'NUMÉRICO') . ' – parece válido');
        } else {
            error_log('[ReembolsoService] DIAGNÓSTICO TX ID: wompi_tx_id="' . $wompiTxId
                . '" NO coincide con formato UUID ni numérico de Wompi.'
                . ' Confirmar que sea el campo "id" del evento Wompi (no REFERENCIA_WOMPI ni otro).');
        }
        
        if (!$soportaRefundApi) {

            $msg = 'Metodo ' . $metodoPago .
                ' requiere reembolso manual desde Wompi';

            error_log('[ReembolsoService] ' . $msg);

            $this->model->registrarReembolso(
                $idDevolucion,
                'PENDIENTE-MANUAL',
                '',
                $msg
            );

            return 'PENDIENTE-MANUAL';
        }
        // ── Llamada a Wompi ─────────────────────────────────────────────────
        $result = $this->callWompi($wompiTxId, $amountInCents);

        if ($result['ok']) {
            $wompiRefundId = (string) $result['id_refund'];
            $this->model->registrarReembolso($idDevolucion, 'REALIZADO', $wompiRefundId, '');
            error_log('[ReembolsoService] Estado guardado: REALIZADO | refund_id=' . $wompiRefundId);
            return 'REALIZADO';
        }

        // ── Guardar en BD: "HTTP 422 | {body raw}" – máximo diagnóstico ─────
        $errorDb = mb_substr((string) ($result['raw'] ?? $result['error'] ?? 'error_desconocido'), 0, 480);
        error_log('[ReembolsoService] Estado guardado: PENDIENTE-MANUAL');
        error_log('[ReembolsoService] REEMBOLSO_ERROR → ' . $errorDb);
        $this->model->registrarReembolso($idDevolucion, 'PENDIENTE-MANUAL', '', $errorDb);
        return 'PENDIENTE-MANUAL';
    }
}
