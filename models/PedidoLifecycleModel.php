<?php

class PedidoLifecycleModel {

    private $conn;
    private static array $lastRun = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function expirarPendientes(): void {
        $stmt = oci_parse($this->conn, "BEGIN SP_EXPIRAR_PEDIDOS; END;");
        if (!$stmt) {
            $error = oci_error($this->conn);
            throw new Exception($error['message'] ?? 'No se pudo preparar SP_EXPIRAR_PEDIDOS');
        }

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo ejecutar SP_EXPIRAR_PEDIDOS');
        }

        oci_free_statement($stmt);
    }

    public function expirarPendientesSiNecesario(int $ttl = 120, string $cacheKey = 'pedido_lifecycle_expirar_ts'): bool {
        $now = time();

        if (session_status() === PHP_SESSION_ACTIVE) {
            $lastRun = (int) ($_SESSION[$cacheKey] ?? 0);
            if ($lastRun > 0 && ($now - $lastRun) < $ttl) {
                return false;
            }

            $this->expirarPendientes();
            $_SESSION[$cacheKey] = $now;
            return true;
        }

        $lastRun = (int) (self::$lastRun[$cacheKey] ?? 0);
        if ($lastRun > 0 && ($now - $lastRun) < $ttl) {
            return false;
        }

        $this->expirarPendientes();
        self::$lastRun[$cacheKey] = $now;
        return true;
    }
}
