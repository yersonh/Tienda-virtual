<?php

class WompiApiModel {

    private string $publicKey;
    private string $privateKey;
    private string $integritySecret;
    private string $baseUrl;

    public function __construct() {
        $this->publicKey = trim((string) getenv('WOMPI_PUBLIC_KEY'));
        $this->privateKey = trim((string) getenv('WOMPI_PRIVATE_KEY'));
        $this->integritySecret = trim((string) getenv('WOMPI_INTEGRITY_SECRET'));

        if ($this->publicKey === '' || $this->privateKey === '' || $this->integritySecret === '') {
            throw new RuntimeException('Falta configurar llaves Wompi');
        }

        $this->baseUrl = str_starts_with($this->privateKey, 'prv_test_') || str_starts_with($this->publicKey, 'pub_test_')
            ? 'https://sandbox.wompi.co/v1'
            : 'https://production.wompi.co/v1';
    }

    public function obtenerTransaccion(string $transactionId): array {
        if ($transactionId === '') {
            throw new InvalidArgumentException('ID de transaccion requerido');
        }
        $response = $this->request('GET', '/transactions/' . rawurlencode($transactionId), null, $this->privateKey);
        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    private function request(string $method, string $path, ?array $payload, ?string $bearer): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extension cURL es requerida para Wompi');
        }

        $ch = curl_init($this->baseUrl . $path);
        if (!$ch) {
            throw new RuntimeException('No se pudo inicializar cURL para Wompi');
        }

        $headers = ['Accept: application/json'];
        if ($bearer !== null && $bearer !== '') {
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers
        ]);

        if ($payload !== null) {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $decoded = is_string($body) ? json_decode($body, true) : null;
        if ($body === false || $httpCode < 200 || $httpCode >= 300 || !is_array($decoded)) {
            $message = is_array($decoded)
                ? (string) ($decoded['error']['reason'] ?? $decoded['message'] ?? 'Error Wompi')
                : ('Error Wompi HTTP ' . $httpCode . ' ' . $curlError);
            throw new RuntimeException($message);
        }

        return $decoded;
    }
}
