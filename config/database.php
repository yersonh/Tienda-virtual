<?php
require_once __DIR__ . '/OCI8Wrapper.php';

class Database {
    private static $instance = null;

    public static function getConnection(): OCI8Connection {
        if (self::$instance === null) {
            try {
                if (!extension_loaded('oci8')) {
                    error_log("ERROR CRÍTICO: Extensión oci8 NO está cargada");
                    error_log("Extensiones cargadas: " . implode(', ', get_loaded_extensions()));
                    throw new Exception("Extensión Oracle OCI8 no disponible");
                }

                $user     = getenv('ORACLE_USER')     ?: 'ADMIN';
                $pass     = getenv('ORACLE_PASSWORD')  ?: '';
                $tnsName  = getenv('ORACLE_TNS')       ?: 'bc27bncudfcgiclb_high';
                $walletPath = getenv('TNS_ADMIN')      ?: '/app/wallet';

                putenv("TNS_ADMIN={$walletPath}");
                putenv("LD_LIBRARY_PATH=/opt/oracle/instantclient_21_10");

                error_log("Conectando a Oracle: {$tnsName} | Wallet: {$walletPath}");

                error_log("TNS_ADMIN actual: " . getenv('TNS_ADMIN'));
                error_log("Wallet files: " . implode(', ', glob($walletPath . '/*') ?: []));

                $conn = oci_connect($user, $pass, $tnsName, 'AL32UTF8');

                if (!$conn) {
                    $error = oci_error();
                    $msg = $error['message'] ?? 'Sin mensaje de error';
                    error_log("Error Oracle OCI: " . $msg);
                    throw new Exception("Error Oracle: " . $msg);
                }

                self::$instance = new OCI8Connection($conn);
                error_log("Conectado a Oracle Cloud exitosamente: {$tnsName}");

            } catch (Exception $e) {
                error_log("Error de conexión Oracle: " . $e->getMessage());
                throw new Exception("No se pudo conectar: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
?>
