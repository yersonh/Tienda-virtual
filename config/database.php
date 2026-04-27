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
            self::writeWalletFile($walletPath, 'sqlnet.ora', getenv('WALLET_SQLNET_B64'), $walletPath);
            self::writeWalletFile($walletPath, 'tnsnames.ora', getenv('WALLET_TNSNAMES_B64'));

            putenv("TNS_ADMIN=$walletPath");
            $_ENV['TNS_ADMIN'] = $walletPath;
            $_SERVER['TNS_ADMIN'] = $walletPath;

            $connectIdentifier = self::resolveConnectIdentifier(
                trim(getenv('ORACLE_TNS')),
                "$walletPath/tnsnames.ora"
            );

            $conn = oci_connect(
                getenv('ORACLE_USER'),
                getenv('ORACLE_PASSWORD'),
                $connectIdentifier,
                'AL32UTF8'
            );

            if (!$conn) {
                $e = oci_error();
                $aliases = implode(', ', self::getTnsAliases("$walletPath/tnsnames.ora"));
                throw new Exception('Oracle error: ' . ($e['message'] ?? 'desconocido') . ". ORACLE_TNS usado: $connectIdentifier. Aliases disponibles: $aliases");
            }

            self::$instance = new OCI8Connection($conn);
        }

        return self::$instance;
    }

    private static function writeWalletFile(string $walletPath, string $fileName, string $encodedContent, ?string $walletLocation = null): void {
        $decoded = base64_decode($encodedContent, true);
        if ($decoded === false) {
            throw new Exception("Wallet invalida en Base64: $fileName");
        }

        if ($walletLocation !== null && $fileName === 'sqlnet.ora') {
            $decoded = preg_replace(
                '/DIRECTORY\s*=\s*"[^"]*"/i',
                'DIRECTORY="' . $walletLocation . '"',
                $decoded
            );
        }

        if ($fileName === 'tnsnames.ora') {
            $decoded = preg_replace('/^\xEF\xBB\xBF/', '', $decoded);
        }

        $path = "$walletPath/$fileName";
        file_put_contents($path, $decoded);
        chmod($path, 0600);
    }

    private static function resolveConnectIdentifier(string $configuredIdentifier, string $tnsnamesPath): string {
        $aliases = self::getTnsAliases($tnsnamesPath);
        if (in_array($configuredIdentifier, $aliases, true)) {
            return $configuredIdentifier;
        }

        $lowerIdentifier = strtolower($configuredIdentifier);
        foreach ($aliases as $alias) {
            if (strtolower($alias) === $lowerIdentifier) {
                return $alias;
            }
        }

        $highAliases = array_values(array_filter($aliases, fn($alias) => str_ends_with(strtolower($alias), '_high')));
        if (count($highAliases) === 1) {
            return $highAliases[0];
        }

        return $configuredIdentifier;
    }

    private static function getTnsAliases(string $tnsnamesPath): array {
        $contents = file_exists($tnsnamesPath) ? file_get_contents($tnsnamesPath) : '';
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        preg_match_all('/^\s*([A-Za-z0-9_.-]+)\s*=/m', $contents, $matches);
        return $matches[1] ?? [];
    }
}
