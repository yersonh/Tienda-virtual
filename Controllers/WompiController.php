<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/WompiModel.php';

class WompiController {

    private $conn = null;
    private ?WompiModel $wompiModel = null;

    public function __construct() {
    }

    public function webhook(): void {
        header('Content-Type: application/json; charset=utf-8');

        $startTime   = microtime(true);
        $requestTime = date('Y-m-d H:i:s');

        error_log("[Wompi Webhook] ===== INICIO ===== $requestTime | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));

        // ── Metodo HTTP ───────────────────────────────────────────────
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        error_log("[Wompi Webhook] Method: $method");

        if ($method !== 'POST') {
            error_log('[Wompi Webhook] ERROR: Metodo no permitido');
            $this->jsonResponse(405, ['ok' => false, 'message' => 'Metodo no permitido']);
        }

        // ── Variables de entorno ──────────────────────────────────────
        $eventsSecret    = trim((string) getenv('WOMPI_EVENTS_SECRET'));
        $integritySecret = trim((string) getenv('WOMPI_INTEGRITY_SECRET'));
        $privateKey      = trim((string) getenv('WOMPI_PRIVATE_KEY'));

        if ($eventsSecret === '' || $integritySecret === '' || $privateKey === '') {
            error_log('[Wompi Webhook] ERROR: Faltan variables de entorno Wompi (EVENTS_SECRET/INTEGRITY_SECRET/PRIVATE_KEY)');
            $this->jsonResponse(500, ['ok' => false, 'message' => 'Configuracion Wompi incompleta']);
        }

        $keyPrefix = substr($privateKey, 0, 8);
        error_log("[Wompi Webhook] Config OK | key_prefix=$keyPrefix***");

        // ── Leer body ─────────────────────────────────────────────────
        $rawBody = file_get_contents('php://input') ?: '';
        if ($rawBody === '') {
            error_log('[Wompi Webhook] ERROR: Body vacio');
            $this->jsonResponse(400, ['ok' => false, 'message' => 'Body vacio']);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            error_log('[Wompi Webhook] ERROR: JSON invalido | raw=' . substr($rawBody, 0, 300));
            $this->jsonResponse(400, ['ok' => false, 'message' => 'JSON invalido']);
        }

        error_log('[Wompi Webhook] Body recibido (primeros 800): ' . substr($rawBody, 0, 800));

        // ── Extraer datos de la transaccion ───────────────────────────
        $transaction   = $this->extraerTransaccion($payload);
        $transactionId = trim((string) ($transaction['id']        ?? 'N/A'));
        $reference     = trim((string) ($transaction['reference'] ?? 'N/A'));
        $statusRaw     = trim((string) ($transaction['status']    ?? ''));
        $status        = strtoupper($statusRaw);

        error_log("[Wompi Webhook] TX=$transactionId | REF=$reference | STATUS=$status");

        // ── Validar firma del evento ──────────────────────────────────
        $firmaEventoOk = $this->validarFirmaEvento($payload, $eventsSecret);
        if (!$firmaEventoOk) {
            error_log("[Wompi Webhook] ERROR: Firma de evento invalida | TX=$transactionId");
            error_log('[Wompi Webhook] Signature block: ' . json_encode($payload['signature'] ?? []));
            $this->jsonResponse(401, ['ok' => false, 'message' => 'Firma invalida']);
        }
        error_log("[Wompi Webhook] Firma evento OK");

        // ── Validar firma de integridad ───────────────────────────────
        $firmaIntegridadOk = $this->validarFirmaIntegridad($payload, $integritySecret);
        if (!$firmaIntegridadOk) {
            error_log("[Wompi Webhook] ERROR: Firma de integridad invalida | TX=$transactionId REF=$reference");
            $this->jsonResponse(401, ['ok' => false, 'message' => 'Integridad invalida']);
        }
        error_log("[Wompi Webhook] Firma integridad OK");

        // ── Procesar solo estados finales ─────────────────────────────
        $statusesProcesables = ['APPROVED', 'DECLINED', 'ERROR', 'VOIDED'];

        if (!in_array($status, $statusesProcesables, true)) {
            error_log("[Wompi Webhook] Status '$status' no es procesable — respondiendo 200 sin accion");
            $this->jsonResponse(200, ['ok' => true, 'status' => $status !== '' ? $status : null]);
        }

        try {
            // ── Verificar transaccion contra API Wompi ────────────────
            error_log("[Wompi Webhook] Verificando TX $transactionId contra API Wompi...");
            $transaction = $this->verificarTransaccionWompi($transaction, $privateKey);

            $verifiedStatus = strtoupper(trim((string) ($transaction['status'] ?? '')));
            error_log("[Wompi Webhook] Status verificado por API: $verifiedStatus");

            // Si la API confirma un status diferente al del evento, lo registramos
            // pero confiamos en la API (que es la fuente de verdad). Solo fallamos si
            // el status verificado no es procesable (ej: PENDING inesperado).
            if ($verifiedStatus !== $status) {
                error_log("[Wompi Webhook] ADVERTENCIA: status_evento='$status' vs status_api='$verifiedStatus' | TX=$transactionId REF=$reference — usando status de API");
            }

            if (!in_array($verifiedStatus, $statusesProcesables, true)) {
                error_log("[Wompi Webhook] Status verificado '$verifiedStatus' no es procesable (aun PENDING?) — respondiendo 200 sin accion | TX=$transactionId");
                $this->jsonResponse(200, ['ok' => true, 'status' => $verifiedStatus]);
            }

            // ── Construir JSON para guardar ───────────────────────────
            $jsonRespuesta = json_encode([
                'event'                => $payload,
                'verified_transaction' => $transaction
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (!is_string($jsonRespuesta) || $jsonRespuesta === '') {
                $jsonRespuesta = $rawBody;
            }

            // ── Conectar a Oracle ─────────────────────────────────────
            error_log("[Wompi Webhook] Conectando a Oracle...");
            $this->conn       = Database::getConnection();
            $this->wompiModel = new WompiModel($this->conn);
            error_log("[Wompi Webhook] Conexion Oracle OK");

            // ── Registrar transaccion (INSERT/UPDATE PAGO + estado PEDIDO) ─
            error_log("[Wompi Webhook] Llamando registrarTransaccion TX=$transactionId STATUS=$verifiedStatus REF=$reference");
            $this->wompiModel->registrarTransaccion($transaction, $jsonRespuesta);

            // ── Commit ────────────────────────────────────────────────
            error_log("[Wompi Webhook] Ejecutando oci_commit...");
            if (!oci_commit($this->conn)) {
                $ociErr = oci_error($this->conn);
                throw new RuntimeException('oci_commit fallo: ' . ($ociErr['message'] ?? 'error desconocido'));
            }

            $elapsed = round((microtime(true) - $startTime) * 1000);
            error_log("[Wompi Webhook] ===== EXITO TX=$transactionId STATUS=$verifiedStatus REF=$reference en {$elapsed}ms =====");

            $this->jsonResponse(200, [
                'ok'     => true,
                'status' => $verifiedStatus
            ]);

        } catch (Throwable $e) {
            if ($this->conn) {
                @oci_rollback($this->conn);
                error_log('[Wompi Webhook] oci_rollback ejecutado');
            }

            $elapsed = round((microtime(true) - $startTime) * 1000);
            error_log("[Wompi Webhook] ===== ERROR TX=$transactionId en {$elapsed}ms =====");
            error_log('[Wompi Webhook] Excepcion: ' . get_class($e) . ': ' . $e->getMessage());
            error_log('[Wompi Webhook] Archivo: ' . $e->getFile() . ':' . $e->getLine());
            error_log('[Wompi Webhook] Stack: ' . $e->getTraceAsString());
            error_log('[Wompi Webhook] Payload completo: ' . json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $this->jsonResponse(500, ['ok' => false, 'message' => 'No se pudo procesar el webhook']);
        }
    }

    private function extraerTransaccion(array $payload): array {
        $transaction = $payload['data']['transaction'] ?? $payload['transaction'] ?? null;
        return is_array($transaction) ? $transaction : [];
    }

    private function validarFirmaEvento(array $payload, string $eventsSecret): bool {
        $signature  = $payload['signature'] ?? [];
        $checksum   = strtolower((string) ($signature['checksum'] ?? ($_SERVER['HTTP_X_EVENT_CHECKSUM'] ?? '')));
        $properties = $signature['properties'] ?? [];
        $timestamp  = (string) ($payload['timestamp'] ?? '');

        if ($checksum === '' || !is_array($properties) || empty($properties) || $timestamp === '') {
            error_log('[Wompi Webhook] validarFirmaEvento: datos incompletos checksum=' . ($checksum !== '' ? 'OK' : 'VACIO')
                . ' props=' . count($properties) . ' ts=' . $timestamp);
            return false;
        }

        $base = '';
        foreach ($properties as $property) {
            $value = $this->obtenerValorPorRuta($payload['data'] ?? [], (string) $property);
            if ($value === null) {
                $value = $this->obtenerValorPorRuta($payload, (string) $property);
            }
            $base .= is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $calculated = hash('sha256', $base . $timestamp . $eventsSecret);
        $result     = hash_equals($checksum, strtolower($calculated));

        if (!$result) {
            error_log('[Wompi Webhook] validarFirmaEvento MISMATCH | expected=' . strtolower($calculated) . ' got=' . $checksum);
        }

        return $result;
    }

    private function validarFirmaIntegridad(array $payload, string $integritySecret): bool {
        $transaction = $this->extraerTransaccion($payload);
        $reference   = (string) ($transaction['reference'] ?? '');
        $amount      = $transaction['amount_in_cents'] ?? null;
        $currency    = (string) ($transaction['currency'] ?? '');

        if ($reference === '' || $amount === null || $currency === '') {
            error_log('[Wompi Webhook] validarFirmaIntegridad: sin datos suficientes para validar — asumiendo OK');
            return true;
        }

        $expected   = hash('sha256', $reference . (string) $amount . $currency . $integritySecret);
        $candidates = [
            $transaction['signature']['checksum']      ?? null,
            is_string($transaction['signature'] ?? null) ? $transaction['signature'] : null,
            $transaction['integrity_signature']        ?? null,
            $transaction['integrity_checksum']         ?? null,
            $payload['data']['signature']['checksum']  ?? null,
        ];

        $hasCandidate = false;
        foreach ($candidates as $candidate) {
            if (!is_string($candidate) || trim($candidate) === '') {
                continue;
            }
            $hasCandidate = true;
            if (hash_equals(strtolower(trim($candidate)), strtolower($expected))) {
                return true;
            }
        }

        if ($hasCandidate) {
            error_log('[Wompi Webhook] validarFirmaIntegridad MISMATCH | ref=' . $reference . ' amount=' . $amount);
        } else {
            error_log('[Wompi Webhook] validarFirmaIntegridad: sin checksum de integridad en la TX — asumiendo OK');
        }

        return !$hasCandidate;
    }

    private function verificarTransaccionWompi(array $transaction, string $privateKey): array {
        $transactionId = trim((string) ($transaction['id'] ?? ''));
        if ($transactionId === '') {
            throw new InvalidArgumentException('Transaccion Wompi sin id');
        }

        $baseUrl = str_starts_with($privateKey, 'prv_test_')
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';

        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extension cURL es requerida para verificar transacciones Wompi');
        }

        $ch = curl_init($baseUrl . '/transactions/' . rawurlencode($transactionId));
        if (!$ch) {
            throw new RuntimeException('No se pudo inicializar cURL para Wompi');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $privateKey,
                'Accept: application/json'
            ]
        ]);

        $body      = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("[Wompi Webhook] API Wompi TX $transactionId => HTTP $httpCode" . ($curlError ? " cURL=$curlError" : ''));

        if ($body === false || $httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException(
                "No se pudo verificar TX $transactionId en Wompi: HTTP $httpCode " . $curlError
            );
        }

        $response = json_decode((string) $body, true);
        $verified = is_array($response) && is_array($response['data'] ?? null) ? $response['data'] : null;

        if (!is_array($verified)) {
            error_log('[Wompi Webhook] Respuesta invalida de API Wompi: ' . substr((string) $body, 0, 500));
            throw new RuntimeException('Respuesta invalida al verificar la transaccion en Wompi');
        }

        // Validar que los campos clave coincidan con el evento
        foreach (['id', 'reference', 'amount_in_cents', 'currency'] as $field) {
            if (isset($transaction[$field], $verified[$field])
                && (string) $transaction[$field] !== (string) $verified[$field]) {
                throw new RuntimeException(
                    "TX verificada campo '$field' no coincide: evento=[{$transaction[$field]}] api=[{$verified[$field]}]"
                );
            }
        }

        return $verified;
    }

    private function obtenerValorPorRuta(array $source, string $path): mixed {
        $current = $source;
        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }
        return $current;
    }

    private function jsonResponse(int $statusCode, array $payload): void {
        http_response_code($statusCode);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
}
