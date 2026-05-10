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

    public function publicKey(): string {
        return $this->publicKey;
    }

    public function obtenerAceptaciones(): array {
        $response = $this->request('GET', '/merchants/' . rawurlencode($this->publicKey), null, null);
        $data = $response['data'] ?? [];
        $acceptance = $data['presigned_acceptance'] ?? [];
        $personal = $data['presigned_personal_data_auth'] ?? [];

        $acceptanceToken = trim((string) ($acceptance['acceptance_token'] ?? ''));
        $personalToken = trim((string) ($personal['acceptance_token'] ?? ''));
        if ($acceptanceToken === '' || $personalToken === '') {
            throw new RuntimeException('Wompi no retorno tokens de aceptacion completos');
        }

        return [
            'acceptance_token' => $acceptanceToken,
            'acceptance_permalink' => (string) ($acceptance['permalink'] ?? ''),
            'accept_personal_auth' => $personalToken,
            'personal_auth_permalink' => (string) ($personal['permalink'] ?? '')
        ];
    }

    public function crearFuenteTarjeta(string $tokenWompi, string $customerEmail): array {
        $aceptaciones = $this->obtenerAceptaciones();
        $response = $this->request('POST', '/payment_sources', [
            'type' => 'CARD',
            'token' => $tokenWompi,
            'customer_email' => $customerEmail,
            'acceptance_token' => $aceptaciones['acceptance_token'],
            'accept_personal_auth' => $aceptaciones['accept_personal_auth']
        ], $this->privateKey);

        $data = $response['data'] ?? [];
        if (empty($data['id']) || strtoupper((string) ($data['status'] ?? '')) !== 'AVAILABLE') {
            throw new RuntimeException('Wompi no dejo disponible la fuente de pago');
        }

        return $data;
    }

    public function crearTransaccionConFuente(
        int $amountInCents,
        string $currency,
        string $reference,
        string $customerEmail,
        int $paymentSourceId,
        int $installments = 1
    ): array {
        $aceptaciones = $this->obtenerAceptaciones();
        $signature = hash('sha256', $reference . (string) $amountInCents . $currency . $this->integritySecret);

        $response = $this->request('POST', '/transactions', [
            'amount_in_cents' => $amountInCents,
            'currency' => $currency,
            'signature' => $signature,
            'customer_email' => $customerEmail,
            'reference' => $reference,
            'payment_source_id' => $paymentSourceId,
            'payment_method' => [
                'installments' => max(1, $installments)
            ],
            'recurrent' => false,
            'acceptance_token' => $aceptaciones['acceptance_token'],
            'accept_personal_auth' => $aceptaciones['accept_personal_auth']
        ], $this->privateKey);

        return is_array($response['data'] ?? null) ? $response['data'] : $response;
    }

    public function obtenerTransaccion(string $transactionId): array {
        if ($transactionId === '') {
            throw new InvalidArgumentException('ID de transaccion requerido');
        }
        $response = $this->request('GET', '/transactions/' . rawurlencode($transactionId), null, $this->privateKey);
        return is_array($response['data'] ?? null) ? $response['data'] : [];
    }

    public function anularFuentePago(int $paymentSourceId): array {
        if ($paymentSourceId <= 0) {
            throw new InvalidArgumentException('Fuente de pago Wompi invalida');
        }

        $response = $this->request('PUT', '/payment_sources/' . $paymentSourceId . '/void', null, $this->privateKey);
        return is_array($response['data'] ?? null) ? $response['data'] : $response;
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
