<?php
require_once __DIR__ . '/OCI8Wrapper.php';

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $required = [
                'ORACLE_USER',
                'ORACLE_PASSWORD',
                'ORACLE_TNS',
                'WALLET_CWALLET_B64',
                'WALLET_EWALLET_B64',
                'WALLET_SQLNET_B64',
                'WALLET_TNSNAMES_B64'
            ];

            foreach ($required as $var) {
                if (!getenv($var)) {
                    throw new Exception("Falta variable: $var");
                }
            }

            $walletPath = '/tmp/wallet';
            if (!is_dir($walletPath)) {
                mkdir($walletPath, 0700, true);
            }

            self::writeWalletFile($walletPath, 'cwallet.sso', getenv('WALLET_CWALLET_B64'));
            self::writeWalletFile($walletPath, 'ewallet.p12', getenv('WALLET_EWALLET_B64'));
            self::writeWalletFile($walletPath, 'sqlnet.ora', getenv('WALLET_SQLNET_B64'));
            self::writeWalletFile($walletPath, 'tnsnames.ora', getenv('WALLET_TNSNAMES_B64'));

            putenv("TNS_ADMIN=$walletPath");

            $conn = oci_connect(
                getenv('ORACLE_USER'),
                getenv('ORACLE_PASSWORD'),
                getenv('ORACLE_TNS'),
                'AL32UTF8'
            );

            if (!$conn) {
                $e = oci_error();
                throw new Exception('Oracle error: ' . ($e['message'] ?? 'desconocido'));
            }

            self::$instance = new OCI8Connection($conn);
        }

        return self::$instance;
    }

    private static function writeWalletFile(string $walletPath, string $fileName, string $encodedContent): void {
        $decoded = base64_decode($encodedContent, true);
        if ($decoded === false) {
            throw new Exception("Wallet invalida en Base64: $fileName");
        }

        $path = "$walletPath/$fileName";
        file_put_contents($path, $decoded);
        chmod($path, 0600);
    }
}
