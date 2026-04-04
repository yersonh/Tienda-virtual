<?php
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../config/database.php';

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
        
        require_once __DIR__ . '/../views/admin/nav.php';
        require_once __DIR__ . '/../views/admin/productos/index.php';
    }

    // Mostrar formulario crear
    public function crear() {
        Auth::soloAdmin();
        $categorias = $this->model->obtenerCategorias();
        
        require_once __DIR__ . '/../views/admin/nav.php';
        require_once __DIR__ . '/../views/admin/productos/crear.php';
    }

    // Guardar producto nuevo
    public function guardar() {
        Auth::soloAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            
            // Guardar imágenes
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                $this->guardarImagenes($id_producto, $_FILES['imagenes']);
            }
            
            $_SESSION['success'] = "Producto creado exitosamente";
            header("Location: index.php?action=productos");
            exit();
        }
    }

    // Mostrar formulario editar
    public function editar() {
        Auth::soloAdmin();
        $id = $_GET['id'] ?? 0;
        $producto = $this->model->obtenerPorId($id);
        $imagenes = $this->model->obtenerImagenes($id);
        $categorias = $this->model->obtenerCategorias();
        
        require_once __DIR__ . '/../views/admin/nav.php';
        require_once __DIR__ . '/../views/admin/productos/editar.php';
    }

    // Actualizar producto
    public function actualizar() {
        Auth::soloAdmin();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            
            // Guardar nuevas imágenes
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                $this->guardarImagenes($id, $_FILES['imagenes']);
            }
            
            $_SESSION['success'] = "Producto actualizado exitosamente";
            header("Location: index.php?action=productos");
            exit();
        }
    }

    // Eliminar producto
    public function eliminar() {
        Auth::soloAdmin();
        $id = $_GET['id'] ?? 0;
        
        // Eliminar imágenes del servidor
        $imagenes = $this->model->obtenerImagenes($id);
        foreach ($imagenes as $img) {
            $ruta = __DIR__ . '/../' . $img['url'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
        
        $this->model->eliminarImagen($id);
        $this->model->eliminar($id);
        
        $_SESSION['success'] = "Producto eliminado exitosamente";
        header("Location: index.php?action=productos");
        exit();
    }

    public function eliminarImagen() {
        Auth::soloAdmin();
        $id_imagen = $_GET['id'] ?? 0;
        $id_producto = $_GET['producto'] ?? 0;
        
        // Obtener la URL de la imagen usando el modelo
        $img = $this->model->obtenerUrlImagen($id_imagen);
        
        if ($img) {
            $ruta = __DIR__ . '/../' . $img['url'];
            if (file_exists($ruta)) {
                unlink($ruta);
            }
        }
        
        // Eliminar de la BD usando el modelo
        $this->model->eliminarImagen($id_imagen);
        
        $_SESSION['success'] = "Imagen eliminada";
        header("Location: index.php?action=productos_editar&id=" . $id_producto);
        exit();
    }

    private function guardarImagenes($id_producto, $archivos) {
        $carpeta = 'uploads/productos/';
        if (!is_dir(__DIR__ . '/../' . $carpeta)) {
            mkdir(__DIR__ . '/../' . $carpeta, 0777, true);
        }
        
        $orden = 0;
        foreach ($archivos['tmp_name'] as $key => $tmp_name) {
            if ($archivos['error'][$key] === 0) {
                $nombre = time() . '_' . $id_producto . '_' . $key . '.jpg';
                $ruta = $carpeta . $nombre;
                move_uploaded_file($tmp_name, __DIR__ . '/../' . $ruta);
                $this->model->guardarImagen($id_producto, $ruta, $orden);
                $orden++;
            }
        }
    }
}
?>