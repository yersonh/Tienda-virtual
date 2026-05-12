<?php
require_once __DIR__ . '/../models/DevolucionModel.php';

class ReembolsoService {

    private $conn;
    private DevolucionModel $model;

    public function __construct($conn, DevolucionModel $model) {
        $this->conn  = $conn;
        $this->model = $model;
    }

    // ─── WOMPI API ────────────────────────────────────────────────────────────

    private function callWompi(string $transactionId, int $amountInCents): array {
        $privateKey = trim((string) getenv('WOMPI_PRIVATE_KEY'));
        if ($privateKey === '') {
            error_log('[ReembolsoService] ABORT: WOMPI_PRIVATE_KEY no configurada');
            return ['ok' => false, 'error' => 'WOMPI_PRIVATE_KEY no configurada'];
        }

        $isSandbox = str_starts_with($privateKey, 'prv_test_');
        $baseUrl   = $isSandbox
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';

        $url  = $baseUrl . '/transactions/' . rawurlencode($transactionId) . '/refund';
        $body = json_encode(['amount_in_cents' => $amountInCents]);

        // ── Log completo de la request ──────────────────────────────────────
        $keyPrefix = substr($privateKey, 0, 16) . '...';
        error_log('[ReembolsoService] ─── REQUEST ───────────────────────────');
        error_log('[ReembolsoService] endpoint        : ' . $url);
        error_log('[ReembolsoService] transaction_id  : ' . $transactionId);
        error_log('[ReembolsoService] amount_in_cents : ' . $amountInCents);
        error_log('[ReembolsoService] payload         : ' . $body);
        error_log('[ReembolsoService] env             : ' . ($isSandbox ? 'SANDBOX' : 'PRODUCCION'));
        error_log('[ReembolsoService] key_prefix      : ' . $keyPrefix);

        $ch = curl_init($url);
        if (!$ch) {
            error_log('[ReembolsoService] ERROR: curl_init() devolvió false');
            return ['ok' => false, 'error' => 'No se pudo inicializar cURL'];
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

        // ── Log completo de la response ─────────────────────────────────────
        error_log('[ReembolsoService] ─── RESPONSE ──────────────────────────');
        error_log('[ReembolsoService] http_code    : ' . $httpCode);
        error_log('[ReembolsoService] curl_error   : ' . ($curlError !== '' ? $curlError : '(ninguno)'));
        error_log('[ReembolsoService] body_raw     : ' . ($responseBody === false ? '(false/error cURL)' : (string) $responseBody));

        if ($responseBody === false) {
            $msg = 'cURL falló: ' . $curlError;
            error_log('[ReembolsoService] RESULT: FAIL – ' . $msg);
            return ['ok' => false, 'error' => $msg];
        }

        $parsed = json_decode((string) $responseBody, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $refundId = (string) ($parsed['data']['id'] ?? $parsed['id'] ?? $transactionId . '-REFUND');
            error_log('[ReembolsoService] RESULT: OK – refund_id=' . $refundId);
            return ['ok' => true, 'id_refund' => $refundId];
        }

        // Wompi puede devolver el error en distintos formatos; intentamos todos.
        $errType   = (string) ($parsed['error']['type']             ?? '');
        $errReason = (string) ($parsed['error']['reason']           ?? '');
        $errMsgInner = (string) ($parsed['error']['message']        ?? '');
        $errMsgTop   = (string) ($parsed['message']                 ?? '');
        $errMessages = (array)  ($parsed['error']['messages']       ?? []);
        $firstMsg    = !empty($errMessages) ? (string) reset($errMessages) : '';

        $human  = $errReason ?: $errMsgInner ?: $firstMsg ?: $errMsgTop ?: 'sin_mensaje';
        $prefix = $errType !== '' ? '[' . $errType . '] ' : '';
        $final  = 'HTTP ' . $httpCode . ': ' . $prefix . $human;

        error_log('[ReembolsoService] RESULT: FAIL – ' . $final);
        error_log('[ReembolsoService] error_block: ' . json_encode($parsed['error'] ?? $parsed));

        // Truncar para no desbordar la columna de la BD (VARCHAR2 típico 500)
        return ['ok' => false, 'error' => mb_substr($final, 0, 480)];
    }

    // ─── FLUJO PRINCIPAL ──────────────────────────────────────────────────────

    /**
     * Ejecuta el reembolso Wompi para un detalle aprobado y registra el estado en DEVOLUCION.
     * NO lanza excepción si Wompi falla: registra PENDIENTE-MANUAL para revisión manual.
     *
     * @return string  'REALIZADO' | 'PENDIENTE-MANUAL' | 'SIN-TRANSACCION'
     */
    public function procesarReembolso(int $idDevolucion, array $detalle): string {
        $cantidadAprobada = max(1, (int) ($detalle['cantidad_aprobada'] ?? 1));
        $idDetalle        = (int)   ($detalle['id_detalle']       ?? 0);
        $precioUnitario   = (float) ($detalle['precio_unitario']  ?? 0);

        if ($precioUnitario <= 0 && $idDetalle > 0) {
            $precioUnitario = $this->model->obtenerPrecioUnitarioDetalle($idDetalle);
        }

        // ── Monto: se usa el total real de la venta para el reembolso Wompi ─
        // En modo sandbox/test Wompi no procesa reembolsos reales; usamos 1500 COP
        $testMode = strtolower(trim((string) getenv('WOMPI_TEST_MODE')));
        $isTest   = in_array($testMode, ['1', 'true', 'yes', 'on'], true);

         // Monto fijo temporal para pruebas de reembolso
        $montoCOP      = 1500;
        $amountInCents = $montoCOP * 100;

        // ── Buscar la transacción Wompi aprobada para este pedido ────────────
        $devolucion  = $this->model->obtenerDevolucionPorId($idDevolucion);
        $transaccion = $devolucion
            ? $this->model->obtenerTransaccionWompiPorPedido((int) $devolucion['id_pedido'])
            : null;

        error_log('[ReembolsoService] ─── procesarReembolso ─────────────────');
        error_log('[ReembolsoService] id_devolucion  : ' . $idDevolucion);
        error_log('[ReembolsoService] id_pedido      : ' . ($devolucion['id_pedido'] ?? 'NULL'));
        error_log('[ReembolsoService] id_detalle     : ' . $idDetalle);
        error_log('[ReembolsoService] precio_unitario: ' . $precioUnitario);
        error_log('[ReembolsoService] cantidad_apro  : ' . $cantidadAprobada);
        error_log('[ReembolsoService] monto_cop      : ' . $montoCOP);
        error_log('[ReembolsoService] amount_in_cents: ' . $amountInCents);
        error_log('[ReembolsoService] is_test_mode   : ' . ($isTest ? 'SI' : 'NO'));
        error_log('[ReembolsoService] transaccion_row: ' . json_encode($transaccion));

        if (!$transaccion || ($transaccion['id_transaccion_wompi'] ?? '') === '') {
            $errMsg = 'Sin transacción Wompi APPROVED para el pedido #' . ($devolucion['id_pedido'] ?? '?');
            error_log('[ReembolsoService] ABORT: ' . $errMsg);
            $this->model->registrarReembolso($idDevolucion, 'SIN-TRANSACCION', '', mb_substr($errMsg, 0, 480));
            return 'SIN-TRANSACCION';
        }

        $wompiTxId = (string) $transaccion['id_transaccion_wompi'];
        $result    = $this->callWompi($wompiTxId, $amountInCents);

        if ($result['ok']) {
            $wompiRefundId = (string) $result['id_refund'];
            $this->model->registrarReembolso($idDevolucion, 'REALIZADO', $wompiRefundId, '');
            error_log('[ReembolsoService] Estado guardado: REALIZADO (refund_id=' . $wompiRefundId . ')');
            return 'REALIZADO';
        }

        $error = mb_substr((string) $result['error'], 0, 480);
        error_log('[ReembolsoService] Estado guardado: PENDIENTE-MANUAL | error=' . $error);
        $this->model->registrarReembolso($idDevolucion, 'PENDIENTE-MANUAL', '', $error);
        return 'PENDIENTE-MANUAL';
    }
}
