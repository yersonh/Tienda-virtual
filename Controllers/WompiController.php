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

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->jsonResponse(405, ['ok' => false, 'message' => 'Metodo no permitido']);
        }

        $eventsSecret = trim((string) getenv('WOMPI_EVENTS_SECRET'));
        $integritySecret = trim((string) getenv('WOMPI_INTEGRITY_SECRET'));
        if ($eventsSecret === '' || $integritySecret === '') {
            error_log('Wompi webhook: faltan WOMPI_EVENTS_SECRET o WOMPI_INTEGRITY_SECRET');
            $this->jsonResponse(500, ['ok' => false, 'message' => 'Configuracion Wompi incompleta']);
        }

        $rawBody = file_get_contents('php://input') ?: '';
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $this->jsonResponse(400, ['ok' => false, 'message' => 'JSON invalido']);
        }

        if (!$this->validarFirmaEvento($payload, $eventsSecret)) {
            error_log('Wompi webhook: firma de evento invalida');
            $this->jsonResponse(401, ['ok' => false, 'message' => 'Firma invalida']);
        }

        if (!$this->validarFirmaIntegridad($payload, $integritySecret)) {
            error_log('Wompi webhook: firma de integridad invalida');
            $this->jsonResponse(401, ['ok' => false, 'message' => 'Integridad invalida']);
        }

        $transaction = $this->extraerTransaccion($payload);
        $status = strtoupper(trim((string) ($transaction['status'] ?? '')));

        try {
            if ($status === 'APPROVED') {
                $this->conn = Database::getConnection();
                $this->wompiModel = new WompiModel($this->conn);
                $this->wompiModel->registrarTransaccionAprobada($transaction, $rawBody);
            }

            $this->jsonResponse(200, [
                'ok' => true,
                'status' => $status !== '' ? $status : null
            ]);
        } catch (Throwable $e) {
            if ($this->conn) {
                @oci_rollback($this->conn);
            }
            error_log('Wompi webhook error: ' . $e->getMessage());
            $this->jsonResponse(500, ['ok' => false, 'message' => 'No se pudo procesar el webhook']);
        }
    }

    private function extraerTransaccion(array $payload): array {
        $transaction = $payload['data']['transaction'] ?? $payload['transaction'] ?? null;
        return is_array($transaction) ? $transaction : [];
    }

    private function validarFirmaEvento(array $payload, string $eventsSecret): bool {
        $signature = $payload['signature'] ?? [];
        $checksum = strtolower((string) ($signature['checksum'] ?? ''));
        $properties = $signature['properties'] ?? [];
        $timestamp = (string) ($payload['timestamp'] ?? '');

        if ($checksum === '' || !is_array($properties) || empty($properties) || $timestamp === '') {
            return false;
        }

        $base = '';
        foreach ($properties as $property) {
            $value = $this->obtenerValorPorRuta($payload['data'] ?? [], (string) $property);
            if ($value === null) {
                $value = $this->obtenerValorPorRuta($payload, (string) $property);
            }
            $base .= is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $calculated = hash('sha256', $base . $timestamp . $eventsSecret);
        return hash_equals($checksum, strtolower($calculated));
    }

    private function validarFirmaIntegridad(array $payload, string $integritySecret): bool {
        $transaction = $this->extraerTransaccion($payload);
        $reference = (string) ($transaction['reference'] ?? '');
        $amount = $transaction['amount_in_cents'] ?? null;
        $currency = (string) ($transaction['currency'] ?? '');

        if ($reference === '' || $amount === null || $currency === '') {
            return true;
        }

        $expected = hash('sha256', $reference . (string) $amount . $currency . $integritySecret);
        $candidates = [
            $transaction['signature']['checksum'] ?? null,
            $transaction['integrity_signature'] ?? null,
            $transaction['integrity_checksum'] ?? null,
            $payload['data']['signature']['checksum'] ?? null,
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

        return !$hasCandidate;
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
