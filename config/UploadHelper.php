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
            
            // Redimensionar imagen si es muy grande
            self::redimensionarImagen($rutaAbsoluta, $extension);
            
            return $rutaRelativa;
        }
        
        throw new Exception("Error al guardar la imagen");
    }
    
    // Redimensionar imagen (opcional)
    private static function redimensionarImagen($ruta, $extension) {
        // Solo redimensionar si la extensión es jpg o jpeg
        if (!in_array($extension, ['jpg', 'jpeg'])) {
            return;
        }
        
        // Cargar la imagen
        $imagen = imagecreatefromjpeg($ruta);
        if (!$imagen) {
            return;
        }
        
        // Obtener dimensiones actuales
        $ancho_actual = imagesx($imagen);
        $alto_actual = imagesy($imagen);
        
        // Verificar si necesita redimensionar
        if ($ancho_actual <= self::$config['max_width'] && $alto_actual <= self::$config['max_height']) {
            imagedestroy($imagen);
            return;
        }
        
        // Calcular nuevas dimensiones manteniendo proporción
        $ratio_ancho = self::$config['max_width'] / $ancho_actual;
        $ratio_alto = self::$config['max_height'] / $alto_actual;
        $ratio = min($ratio_ancho, $ratio_alto);
        
        $nuevo_ancho = round($ancho_actual * $ratio);
        $nuevo_alto = round($alto_actual * $ratio);
        
        // Crear imagen redimensionada
        $nueva_imagen = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
        imagecopyresampled($nueva_imagen, $imagen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho_actual, $alto_actual);
        
        // Guardar imagen redimensionada
        imagejpeg($nueva_imagen, $ruta, self::$config['jpeg_quality']);
        
        // Liberar memoria
        imagedestroy($imagen);
        imagedestroy($nueva_imagen);
        
        error_log("Imagen redimensionada: " . $ruta);
    }
    
    // Obtener la URL para mostrar la imagen usando image.php
    public static function getImageUrl($rutaRelativa) {
        // Si la ruta está vacía, devolver la imagen por defecto
        if (empty($rutaRelativa)) {
            return self::getDefaultPhoto();
        }
        
        // Extraer el nombre del archivo y la carpeta
        // Ejemplo: uploads/productos/1775266610_17_0.jpg
        $partes = explode('/', $rutaRelativa);
        $carpeta = $partes[1] ?? 'productos';
        $archivo = end($partes);
        
        return 'image.php?folder=' . $carpeta . '&path=' . urlencode($archivo);
    }
    
    // Ruta base para el volumen de devoluciones en Railway
    public static function getDevolucionesBasePath(): string {
        // Railway monta el volumen en /volume/
        $volumePath = '/volume/devoluciones/';
        if (is_dir($volumePath) || @mkdir($volumePath, 0755, true)) {
            return $volumePath;
        }
        // Fallback local (desarrollo)
        $localPath = __DIR__ . '/../../public/uploads/devoluciones/';
        if (!is_dir($localPath)) {
            @mkdir($localPath, 0755, true);
        }
        return $localPath;
    }

    // Crear directorio para imágenes de devoluciones
    public static function ensureDevolucionesDirectory(): string {
        $path = self::getDevolucionesBasePath();
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        return $path;
    }

    // Procesar imagen subida para devoluciones (segura: mime real, uniqid, volume path)
    public static function procesarImagenDevolucion(array $archivo, int $id_devolucion_detalle, int $orden): string {
        // Validar errores PHP upload
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Error en la subida del archivo (código ' . ($archivo['error'] ?? -1) . ').');
        }

        // Validar tamaño
        if (($archivo['size'] ?? 0) > self::$config['max_size']) {
            throw new Exception('El archivo excede el tamaño máximo de 5 MB.');
        }

        // Verificar MIME real (no confiar en el tipo declarado por el cliente)
        $mimeReal = mime_content_type($archivo['tmp_name']);
        $mimeExtMap = [
            'image/jpeg' => 'jpg',
            'image/jpg'  => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($mimeExtMap[$mimeReal])) {
            throw new Exception('Tipo de archivo no permitido. Solo se aceptan JPG, PNG y WEBP.');
        }

        $extension    = $mimeExtMap[$mimeReal];
        $nombre       = uniqid('DEV_', true) . '.' . $extension;
        $directorio   = self::ensureDevolucionesDirectory();
        $rutaAbsoluta = $directorio . $nombre;
        // Ruta relativa guardada en Oracle: /devoluciones/archivo.ext
        $rutaRelativa = '/devoluciones/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
            throw new Exception('No se pudo guardar la imagen de devolución. Verifica permisos en ' . $directorio);
        }

        error_log('[UploadHelper] imagen devolución guardada: ' . $rutaAbsoluta);
        return $rutaRelativa;
    }

    // Obtener la URL para imágenes de devoluciones (usa image.php como proxy)
    public static function getDevolucionImageUrl(string $rutaRelativa): string {
        if (empty($rutaRelativa)) {
            return self::getDefaultPhoto();
        }
        $archivo = basename($rutaRelativa);
        return 'image.php?folder=devoluciones&path=' . urlencode($archivo);
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