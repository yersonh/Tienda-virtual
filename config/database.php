<?php
class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {

            // 🔍 Validar variables
            $required = [
                'ORACLE_USER','ORACLE_PASSWORD','ORACLE_TNS',
                'WALLET_CWALLET_B64','WALLET_EWALLET_B64',
                'WALLET_SQLNET_B64','WALLET_TNSNAMES_B64'
            ];
            foreach ($required as $var) {
                if (!getenv($var)) {
                    throw new Exception("Falta variable: $var");
                }
            }

            // 📁 Carpeta temporal
            $walletPath = '/tmp/wallet';
            if (!file_exists($walletPath)) {
                mkdir($walletPath, 0700, true);
            }

            // 🔐 Reconstruir wallet
            file_put_contents("$walletPath/cwallet.sso", base64_decode(getenv('WALLET_CWALLET_B64')));
            file_put_contents("$walletPath/ewallet.p12", base64_decode(getenv('WALLET_EWALLET_B64')));
            file_put_contents("$walletPath/sqlnet.ora", base64_decode(getenv('WALLET_SQLNET_B64')));
            file_put_contents("$walletPath/tnsnames.ora", base64_decode(getenv('WALLET_TNSNAMES_B64')));

            putenv("TNS_ADMIN=$walletPath");

            // 🔌 Conexión
            $conn = oci_connect(
                getenv("ORACLE_USER"),
                getenv("ORACLE_PASSWORD"),
                getenv("ORACLE_TNS"),
                'AL32UTF8'
            );

            // 📁 Crear carpeta /tmp/wallet
            $walletPath = '/tmp/wallet';

            if (!file_exists($walletPath)) {
                mkdir($walletPath, 0777, true);
            }

            // 🔐 Decodificar variables Base64
            file_put_contents("$walletPath/cwallet.sso", base64_decode(getenv('WALLET_CWALLET_B64')));
            file_put_contents("$walletPath/ewallet.pem", base64_decode(getenv('WALLET_EWALLET_B64')));
            file_put_contents("$walletPath/sqlnet.ora", base64_decode(getenv('WALLET_SQLNET_B64')));
            file_put_contents("$walletPath/tnsnames.ora", base64_decode(getenv('WALLET_TNSNAMES_B64')));

            // 🔗 Configurar TNS_ADMIN
            putenv("TNS_ADMIN=/tmp/wallet");

            // 🔑 Conectar usando variables de entorno
            $conn = oci_connect(
                getenv("ORACLE_USER"),
                getenv("ORACLE_PASSWORD"),
                getenv("ORACLE_TNS")
            );

            if (!$conn) {
                $e = oci_error();
                throw new Exception("Oracle error: " . $e['message']);
            }

            self::$instance = new OCI8Connection($conn);
        }

        return self::$instance;
    }
}