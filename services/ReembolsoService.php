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
            return ['ok' => false, 'error' => 'WOMPI_PRIVATE_KEY no configurada'];
        }

        $baseUrl = str_starts_with($privateKey, 'prv_test_')
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';

        $url  = $baseUrl . '/transactions/' . rawurlencode($transactionId) . '/refund';
        $body = json_encode(['amount_in_cents' => $amountInCents]);

        $ch = curl_init($url);
        if (!$ch) {
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

        if ($responseBody === false) {
            return ['ok' => false, 'error' => 'Error cURL: ' . $curlError];
        }

        $parsed = json_decode((string) $responseBody, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            $refundId = (string) ($parsed['data']['id'] ?? $parsed['id'] ?? $transactionId . '-REFUND');
            return ['ok' => true, 'id_refund' => $refundId];
        }

        $errMsg = (string) ($parsed['error']['reason'] ?? $parsed['message'] ?? 'Error HTTP ' . $httpCode);
        error_log('[ReembolsoService] Wompi ' . $httpCode . ' – ' . $errMsg);
        return ['ok' => false, 'error' => $errMsg];
    }

    // ─── FLUJO PRINCIPAL ──────────────────────────────────────────────────────

    /**
     * Ejecuta el reembolso Wompi para un detalle aprobado y registra el estado en DEVOLUCION.
     * NO lanza excepción si Wompi falla: registra PENDIENTE-MANUAL para revisión manual.
     *
     * @return string  Estado resultante: 'REALIZADO' | 'PENDIENTE-MANUAL' | 'SIN-TRANSACCION'
     */
    public function procesarReembolso(int $idDevolucion, array $detalle): string {
        $cantidadAprobada = max(1, (int) ($detalle['cantidad_aprobada'] ?? 1));
        $idDetalle        = (int) ($detalle['id_detalle']       ?? 0);
        $precioUnitario   = (float) ($detalle['precio_unitario'] ?? 0);

        if ($precioUnitario <= 0 && $idDetalle > 0) {
            $precioUnitario = $this->model->obtenerPrecioUnitarioDetalle($idDetalle);
        }

        $montoReembolso = 100;
        $amountInCents  = (int) round($montoReembolso * 100);   

        // En modo test Wompi no permite reembolsos reales; usamos un monto simbólico
        $testMode = strtolower(trim((string) getenv('WOMPI_TEST_MODE')));
        if (in_array($testMode, ['1', 'true', 'yes', 'on'], true)) {
            $amountInCents = 150000; // 1 500 COP
        }

        $devolucion  = $this->model->obtenerDevolucionPorId($idDevolucion);
        $transaccion = $devolucion
            ? $this->model->obtenerTransaccionWompiPorPedido((int) $devolucion['id_pedido'])
            : null;

        if (!$transaccion || ($transaccion['id_transaccion_wompi'] ?? '') === '') {
            $this->model->registrarReembolso($idDevolucion, 'SIN-TRANSACCION', '', 'Sin transaccion Wompi aprobada');
            return 'SIN-TRANSACCION';
        }

        $result = $this->callWompi((string) $transaccion['id_transaccion_wompi'], $amountInCents);

        if ($result['ok']) {
            $wompiId = (string) $result['id_refund'];
            $this->model->registrarReembolso($idDevolucion, 'REALIZADO', $wompiId, '');
            return 'REALIZADO';
        }

        $error = (string) $result['error'];
        error_log('[ReembolsoService] Wompi falló devolucion=' . $idDevolucion . ': ' . $error);
        $this->model->registrarReembolso($idDevolucion, 'PENDIENTE-MANUAL', '', $error);
        return 'PENDIENTE-MANUAL';
    }
}
