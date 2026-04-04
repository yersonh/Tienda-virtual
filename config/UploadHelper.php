<?php
// config/UploadHelper.php

class UploadHelper {
    
    // Configuración
    private static $config = [
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'max_size' => 5 * 1024 * 1024, // 5MB
        'jpeg_quality' => 85,
        'max_width' => 1200,
        'max_height' => 1200,
        'profiles_path' => 'profiles/',
        'temp_path' => 'temp/',
        'default_photo' => '/imagenes/imagendefault.png'
    ];
    
    // Obtener la ruta ABSOLUTA del volumen en Railway
    public static function getBasePath() {
        // En Railway, el volumen está montado en /uploads
        $volumePath = '/uploads/';
        
        if (is_dir($volumePath) || mkdir($volumePath, 0755, true)) {
            return $volumePath;
        }
        
        // Fallback para desarrollo local
        $localPath = __DIR__ . '/../../public/uploads/';
        if (!is_dir($localPath)) {
            mkdir($localPath, 0755, true);
        }
        return $localPath;
    }
    
    // Obtener la URL base para las imágenes
    public static function getBaseUrl() {
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
    
    // Crear directorio para productos
    public static function ensureProductosDirectory() {
        $path = self::getBasePath() . 'productos/';
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            error_log("Directorio creado: " . $path);
        }
        return $path;
    }
    
    // Validar y procesar imagen subida
    public static function procesarImagen($archivo, $id_producto, $orden) {
        // Validar tipo
        if (!in_array($archivo['type'], self::$config['allowed_types'])) {
            throw new Exception("Tipo de archivo no permitido. Tipos permitidos: JPG, PNG, GIF, WEBP");
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::$config['allowed_extensions'])) {
            throw new Exception("Extensión no permitida");
        }
        
        // Validar tamaño
        if ($archivo['size'] > self::$config['max_size']) {
            throw new Exception("El archivo excede el tamaño máximo de 5MB");
        }
        
        // Generar nombre único
        $nombre = time() . '_' . $id_producto . '_' . $orden . '.' . $extension;
        
        $directorio = self::ensureProductosDirectory();
        $rutaAbsoluta = $directorio . $nombre;
        $rutaRelativa = 'uploads/productos/' . $nombre;
        
        // Mover archivo
        if (move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            error_log("Imagen guardada en: " . $rutaAbsoluta);
            
            // Opcional: Redimensionar imagen si es muy grande
            self::redimensionarImagen($rutaAbsoluta, $extension);
            
            return $rutaRelativa;
        }
        
        throw new Exception("Error al guardar la imagen");
    }
    
    // Redimensionar imagen (opcional)
    private static function redimensionarImagen($ruta, $extension) {
        // Aquí puedes implementar redimensionamiento con GD si lo necesitas
        // Por ahora solo registramos que se guardó
        error_log("Imagen lista: " . $ruta);
    }
    
    // Obtener configuración
    public static function getConfig() {
        return self::$config;
    }
    
    // Obtener foto por defecto
    public static function getDefaultPhoto() {
        return self::$config['default_photo'];
    }
}
?>