<?php
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            try {
                // Credenciales de Oracle Cloud
                $user = getenv('ORACLE_USER') ?: 'ADMIN';
                $pass = getenv('ORACLE_PASSWORD');
                $tnsName = getenv('ORACLE_TNS') ?: 'bc27bncudfcgiclb_high';
                $walletPath = getenv('TNS_ADMIN') ?: '/app/wallet';

                if (!extension_loaded('pdo_oci')) {
                    error_log("ERROR CRÍTICO: Extensión pdo_oci NO está cargada");
                    error_log("Extensiones cargadas: " . implode(', ', get_loaded_extensions()));
                    throw new Exception("Extensión Oracle PDO no disponible");
                }

                // Configurar TNS_ADMIN para que apunte al directorio del Wallet
                putenv("TNS_ADMIN={$walletPath}");

                // DSN para Oracle usando TNS (del Wallet)
                $dsn = "oci:dbname={$tnsName}";
                error_log("Intentando conectar a Oracle: {$tnsName}");
                error_log("Wallet en: {$walletPath}");

                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_PERSISTENT => false
                ]);

                $test = self::$instance->query("SELECT 1 as test FROM dual")->fetch();
                error_log("Prueba de conexión exitosa: " . print_r($test, true));

                self::$instance->exec("ALTER SESSION SET NLS_CHARACTERSET='AL32UTF8'");
                self::$instance->exec("ALTER SESSION SET NLS_DATE_FORMAT='YYYY-MM-DD HH24:MI:SS'");

                error_log("Conectado a Oracle Cloud: {$tnsName}");

            } catch (PDOException $e) {
                error_log("Error Oracle: " . $e->getMessage());
                error_log("Código de error: " . $e->getCode());
                error_log("Archivo: " . $e->getFile() . " línea: " . $e->getLine());
                throw new Exception("No se pudo conectar a la base de datos Oracle. Contacte al administrador.");
            }
        }
        return self::$instance;
    }
}
?>