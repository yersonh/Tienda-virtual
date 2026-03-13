<?php
class Database {
    private static $instance = null;
    
    public static function getConnection() {
        if (self::$instance === null) {
            try {
                $host = 'maglev.proxy.rlwy.net';
                $port = '25531';
                $dbname = 'railway';
                $user = 'postgres';
                $pass = 'tgdyECzQNOReFfmjcnRlwdQJjxddNhnu';
                
                // Verificar si la extensión PDO_PGSQL está cargada
                if (!extension_loaded('pdo_pgsql')) {
                    error_log("ERROR CRÍTICO: Extensión pdo_pgsql NO está cargada");
                    error_log("Extensiones cargadas: " . implode(', ', get_loaded_extensions()));
                    throw new Exception("Extensión PostgreSQL no disponible");
                }
                
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                error_log("Intentando conectar a: {$dsn}");
                
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_PERSISTENT => false
                ]);
                
                // Probar la conexión con una consulta simple
                $test = self::$instance->query("SELECT 1 as test")->fetch();
                error_log("Prueba de consulta exitosa: " . print_r($test, true));
                
                // Configurar UTF-8
                self::$instance->exec("SET NAMES 'UTF8'");
                self::$instance->exec("SET timezone = 'America/Bogota'");
                
                error_log("Conectado a PostgreSQL: {$host}:{$port}/{$dbname}");
                
            } catch (PDOException $e) {
                error_log("Error PostgreSQL: " . $e->getMessage());
                error_log("Código de error: " . $e->getCode());
                error_log("Archivo: " . $e->getFile() . " línea: " . $e->getLine());
                throw new Exception("No se pudo conectar a la base de datos. Contacte al administrador.");
            }
        }
        return self::$instance;
    }
    
    public static function getUploadsPath() {
        return '/uploads/';
    }
    
    public static function getUploadsUrl() {
        $isHttps = false;
        
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            $isHttps = true;
        }
        
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $isHttps = true;
        }
        
        if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            $isHttps = true;
        }
        
        $protocol = $isHttps ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        return $protocol . $host . '/uploads/';
    }
    
    public static function ensureUploadsDirectory() {
        $uploadsPath = self::getUploadsPath();
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
            error_log("Directorio de uploads creado: {$uploadsPath}");
        }
        $profilesDir = $uploadsPath . 'profiles/';
        $tempDir = $uploadsPath . 'temp/';
        
        if (!is_dir($profilesDir)) {
            mkdir($profilesDir, 0755, true);
            error_log("Directorio de perfiles creado: {$profilesDir}");
        }
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
            error_log("Directorio temporal creado: {$tempDir}");
        }
        
        return $uploadsPath;
    }
}