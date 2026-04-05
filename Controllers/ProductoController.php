<?php
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/UploadHelper.php';

class ProductoController {

    private $model;

    public function __construct() {
        $pdo = Database::getConnection();
        $this->model = new ProductoModel($pdo);
    }

    // Listar productos
    public function index() {
        Auth::soloAdmin();
        $productos = $this->model->obtenerTodos();
        
        foreach ($productos as $key => $producto) {
            $productos[$key]['imagenes'] = $this->model->obtenerImagenes($producto['id_producto']);
        }
        
        ob_start();
        require_once __DIR__ . '/../views/admin/productos/index.php';
        $contenido = ob_get_clean();
        
        require_once __DIR__ . '/../views/admin/nav.php';
    }

    // Mostrar formulario crear
    public function crear() {
        Auth::soloAdmin();
        $categorias = $this->model->obtenerCategorias();
        
        ob_start();
        require_once __DIR__ . '/../views/admin/productos/crear.php';
        $contenido = ob_get_clean();
        
        require_once __DIR__ . '/../views/admin/nav.php';
    }

    // Guardar producto nuevo (CORREGIDO)
    public function guardar() {
        Auth::soloAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $datos = [
                    'nombre' => $_POST['nombre'],
                    'codigo' => $_POST['codigo'],
                    'descripcion' => $_POST['descripcion'],
                    'precio' => $_POST['precio'],
                    'stock' => $_POST['stock'],
                    'estado' => $_POST['estado'],
                    'id_categoria' => $_POST['id_categoria']
                ];
                
                $id_producto = $this->model->crear($datos);
                
                if (!$id_producto) {
                    throw new Exception('Error al crear el producto');
                }
                
                // Guardar imágenes usando UploadHelper
                if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                    $this->guardarImagenes($id_producto, $_FILES['imagenes']);
                }
                
                $_SESSION['success'] = "Producto creado exitosamente";
                header("Location: index.php?action=productos");
                exit();
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: index.php?action=productos_crear");
                exit();
            }
        }
    }

    // Mostrar formulario editar
    public function editar() {
        Auth::soloAdmin();
        $id = $_GET['id'] ?? 0;
        $producto = $this->model->obtenerPorId($id);
        $imagenes = $this->model->obtenerImagenes($id);
        $categorias = $this->model->obtenerCategorias();
        
        ob_start();
        require_once __DIR__ . '/../views/admin/productos/editar.php';
        $contenido = ob_get_clean();
        
        require_once __DIR__ . '/../views/admin/nav.php';
    }

    // Actualizar producto
    public function actualizar() {
        Auth::soloAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id_producto'];
                $datos = [
                    'nombre' => $_POST['nombre'],
                    'codigo' => $_POST['codigo'],
                    'descripcion' => $_POST['descripcion'],
                    'precio' => $_POST['precio'],
                    'stock' => $_POST['stock'],
                    'estado' => $_POST['estado'],
                    'id_categoria' => $_POST['id_categoria']
                ];
                
                $this->model->actualizar($id, $datos);
                
                // Guardar nuevas imágenes usando UploadHelper
                if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                    $this->guardarImagenes($id, $_FILES['imagenes']);
                }
                
                $_SESSION['success'] = "Producto actualizado exitosamente";
                header("Location: index.php?action=productos");
                exit();
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                header("Location: index.php?action=productos_editar&id=" . $_POST['id_producto']);
                exit();
            }
        }
    }
    
    // Ver detalle del producto
    public function ver() {
        Auth::soloAdmin();
        $id = $_GET['id'] ?? 0;
        $producto = $this->model->obtenerPorId($id);
        $imagenes = $this->model->obtenerImagenes($id);
        
        ob_start();
        require_once __DIR__ . '/../views/admin/productos/ver.php';
        $contenido = ob_get_clean();
        
        require_once __DIR__ . '/../views/admin/nav.php';
    }
    
    // Eliminar producto COMPLETO (con todas sus imágenes)
    public function eliminar() {
        Auth::soloAdmin();
        $id = $_GET['id'] ?? 0;
        
        // Eliminar archivos del servidor usando UploadHelper
        $imagenes = $this->model->obtenerImagenes($id);
        foreach ($imagenes as $img) {
            // Extraer la ruta relativa del volumen
            $rutaRelativa = str_replace('/uploads/', '', $img['url']);
            $rutaAbsoluta = UploadHelper::getBasePath() . $rutaRelativa;
            
            if (file_exists($rutaAbsoluta)) {
                unlink($rutaAbsoluta);
                error_log("Archivo eliminado: " . $rutaAbsoluta);
            }
        }
        
        // Eliminar TODAS las imágenes de la BD
        $this->model->eliminarImagenes($id);
        
        // Eliminar el producto
        $this->model->eliminar($id);
        
        $_SESSION['success'] = "Producto eliminado exitosamente";
        header("Location: index.php?action=productos");
        exit();
    }

    // Eliminar UNA sola imagen (desde el formulario de editar)
    public function eliminarImagen() {
        Auth::soloAdmin();
        $id_imagen = $_GET['id'] ?? 0;
        $id_producto = $_GET['producto'] ?? 0;
        
        // Obtener la URL de la imagen
        $img = $this->model->obtenerUrlImagen($id_imagen);
        
        if ($img) {
            // Extraer la ruta relativa del volumen
            $rutaRelativa = str_replace('/uploads/', '', $img['url']);
            $rutaAbsoluta = UploadHelper::getBasePath() . $rutaRelativa;
            
            if (file_exists($rutaAbsoluta)) {
                unlink($rutaAbsoluta);
                error_log("Archivo eliminado: " . $rutaAbsoluta);
            }
        }
        
        // Eliminar SOLO esa imagen de la BD
        $this->model->eliminarImagen($id_imagen);
        
        $_SESSION['success'] = "Imagen eliminada";
        header("Location: index.php?action=productos_editar&id=" . $id_producto);
        exit();
    }

    // MÉTODO CORREGIDO: guardarImagenes
    private function guardarImagenes($id_producto, $archivos) {
        // Verificar que se recibieron archivos
        if (empty($archivos) || empty($archivos['tmp_name'][0])) {
            return;
        }
        
        // Obtener el orden actual de las imágenes existentes
        $imagenesExistentes = $this->model->obtenerImagenes($id_producto);
        $orden = count($imagenesExistentes);
        
        // Procesar cada archivo
        $totalArchivos = count($archivos['name']);
        
        for ($i = 0; $i < $totalArchivos; $i++) {
            // Verificar que no haya error en el archivo
            if ($archivos['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // Verificar que sea una imagen válida
            $tipoPermitido = ['image/jpeg', 'image/png', 'image/jpg'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $archivos['tmp_name'][$i]);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $tipoPermitido)) {
                error_log("Tipo de archivo no permitido: " . $mimeType);
                continue;
            }
            
            // Verificar tamaño (5MB máximo)
            if ($archivos['size'][$i] > 5 * 1024 * 1024) {
                error_log("Archivo demasiado grande: " . $archivos['size'][$i] . " bytes");
                continue;
            }
            
            $archivo = [
                'name' => $archivos['name'][$i],
                'type' => $archivos['type'][$i],
                'tmp_name' => $archivos['tmp_name'][$i],
                'size' => $archivos['size'][$i],
                'error' => $archivos['error'][$i]
            ];
            
            try {
                // Usar UploadHelper para procesar y guardar la imagen
                $rutaRelativa = UploadHelper::procesarImagen($archivo, $id_producto, $orden);
                
                if ($rutaRelativa) {
                    $this->model->guardarImagen($id_producto, $rutaRelativa, $orden);
                    $orden++;
                    error_log("Imagen guardada exitosamente: " . $rutaRelativa);
                } else {
                    error_log("UploadHelper::procesarImagen retornó false o null");
                }
                
            } catch (Exception $e) {
                error_log("Error al guardar imagen: " . $e->getMessage());
                $_SESSION['error'] = "Error al guardar algunas imágenes: " . $e->getMessage();
            }
        }
    }
}
?>