<?php

class PedidoLifecycleModel {

    private $conn;

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
}
