<?php
class Database {
    private static $instance = null;
    
    public static function getConnection() {
        if (self::$instance === null) {
            try {
                $host = 'gondola.proxy.rlwy.net';
                $port = '30822';
                $dbname = 'railway';
                $user = 'postgres';
                $pass = 'stBpxqYbXIJfFJrqEOAVLlAergRlfETB';
                
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_PERSISTENT => false
                ]);
                
                // Configurar UTF-8
                self::$instance->exec("SET NAMES 'UTF8'");
                self::$instance->exec("SET timezone = 'America/Bogota'");
                
                error_log("Conectado a PostgreSQL: {$host}:{$port}/{$dbname}");
                
            } catch (PDOException $e) {
                error_log("Error PostgreSQL: " . $e->getMessage());
                throw new Exception("No se pudo conectar a la base de datos. Contacte al administrador.");
            }
        }
        return self::$instance;
    }
    
    public static function getUploadsPath() {
        // En Railway con Persistent Volume
        return '/uploads/';
    }
    
public static function getUploadsUrl() {
    // Para consistencia, usar el mismo método que FileHelper::buildUrl()
    $isHttps = false;
    
    // 1. Railway usa este header
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        $isHttps = true;
    }
    
    // 2. Verificar header estándar
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        $isHttps = true;
    }
    
    // 3. Verificar otros headers de proxy
    if (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        $isHttps = true;
    }
    
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    return $protocol . $host . '/uploads/';
}
    
    // Validar que el directorio de uploads existe
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