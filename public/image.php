<?php
// image.php - Servir imágenes desde el volumen de Railway

// Obtener la ruta de la imagen
$ruta = $_GET['path'] ?? '';

if (empty($ruta)) {
    header("HTTP/1.0 404 Not Found");
    die('Imagen no encontrada');
}

// Limpiar la ruta para evitar ataques (path traversal)
$ruta = basename($ruta);
$carpeta = $_GET['folder'] ?? 'productos';

// Validar carpeta permitida
$carpetasPermitidas = ['productos', 'profiles', 'temp'];
if (!in_array($carpeta, $carpetasPermitidas)) {
    header("HTTP/1.0 403 Forbidden");
    die('Acceso denegado');
}

// Construir la ruta absoluta en el volumen
$rutaArchivo = '/uploads/' . $carpeta . '/' . $ruta;

// Verificar si el archivo existe
if (!file_exists($rutaArchivo)) {
    header("HTTP/1.0 404 Not Found");
    die('Archivo no encontrado: ' . $rutaArchivo);
}

$modifiedTime = filemtime($rutaArchivo);
$etag = '"' . md5($rutaArchivo . '|' . $modifiedTime . '|' . filesize($rutaArchivo)) . '"';

header('ETag: ' . $etag);
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modifiedTime) . ' GMT');
header('Cache-Control: public, max-age=604800, immutable');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');

if (
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) ||
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $modifiedTime)
) {
    http_response_code(304);
    exit;
}

// Obtener la extensión del archivo
$extension = strtolower(pathinfo($rutaArchivo, PATHINFO_EXTENSION));

// Establecer el Content-Type según la extensión
switch ($extension) {
    case 'jpg':
    case 'jpeg':
        header('Content-Type: image/jpeg');
        break;
    case 'png':
        header('Content-Type: image/png');
        break;
    case 'gif':
        header('Content-Type: image/gif');
        break;
    case 'webp':
        header('Content-Type: image/webp');
        break;
    default:
        header('Content-Type: application/octet-stream');
        break;
}

// Leer y enviar el archivo
readfile($rutaArchivo);
exit;
?>
