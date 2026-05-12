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

    private function invalidarCacheCatalogo(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        unset($_SESSION['tienda_cache']);
    }

    // Listar productos
    public function index() {
        Auth::soloAdmin();
        $productos = $this->model->obtenerTodos(false) ?? [];

        foreach ($productos as $key => $producto) {
            $imagen = $producto['imagen'] ?? null;
            $productos[$key]['imagenes'] = !empty($imagen)
                ? [['url' => $imagen]]
                : [];
        }

        ob_start();
        require_once __DIR__ . '/../views/admin/productos/index.php';
        $contenido = ob_get_clean();

        require_once __DIR__ . '/../views/admin/nav.php';
    }

    // Mostrar formulario crear
    public function crear() {
        Auth::soloAdmin();
        $categorias = $this->model->obtenerCategorias() ?? [];

        ob_start();
        require_once __DIR__ . '/../views/admin/productos/crear.php';
        $contenido = ob_get_clean();

        require_once __DIR__ . '/../views/admin/nav.php';
    }

    // Guardar producto nuevo
    public function guardar() {
        Auth::soloAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        try {
            $datos = [
                'nombre'       => trim($_POST['nombre'] ?? ''),
                'codigo'       => trim($_POST['codigo'] ?? ''),
                'descripcion'  => trim($_POST['descripcion'] ?? ''),
                'precio'       => $_POST['precio'] ?? '0',
                'estado'       => $_POST['estado'] ?? '1',
                'id_categoria' => $_POST['id_categoria'] ?? 0,
            ];

            // 1. Insertar PRODUCTO (sin auto-commit)
            $id_producto = $this->model->crear($datos, false);

            // 2. Insertar REFERENCIA_PRODUCTO
            $refDatos = [
                'numero_referencia' => trim($_POST['numero_referencia'] ?? ''),
                'marca'             => trim($_POST['ref_marca'] ?? ''),
                'fabricante'        => trim($_POST['fabricante'] ?? ''),
                'especificaciones'  => trim($_POST['especificaciones'] ?? ''),
            ];
            $id_referencia = $this->model->crearReferencia($id_producto, $refDatos);

            // 3. Compatibilidades vehiculo (MARCA_VEHICULO y MODELO_VEHICULO son NOT NULL)
            foreach ($_POST['vehiculos'] ?? [] as $v) {
                if (empty(trim($v['marca_vehiculo'] ?? '')) || empty(trim($v['modelo_vehiculo'] ?? ''))) continue;
                $this->model->crearCompatibilidadVehiculo($id_referencia, $v);
            }

            // 4. Compatibilidades maquinaria (MARCA_MAQUINARIA y MODELO_MAQUINARIA son NOT NULL)
            foreach ($_POST['maquinaria'] ?? [] as $m) {
                if (empty(trim($m['marca_maquinaria'] ?? '')) || empty(trim($m['modelo_maquinaria'] ?? ''))) continue;
                $this->model->crearCompatibilidadMaquinaria($id_referencia, $m);
            }

            // 5. Commit de la transaccion
            $this->model->commit();

            // 6. Guardar imagenes (fuera de la transaccion Oracle; son archivos del SO)
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                $this->guardarImagenes($id_producto, $_FILES['imagenes']);
            }

            $this->invalidarCacheCatalogo();
            $_SESSION['success'] = 'Producto creado exitosamente';
            header('Location: index.php?action=productos');
            exit();

        } catch (Exception $e) {
            $this->model->rollback();
            error_log('Error al crear producto: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al guardar el producto: ' . $e->getMessage();
            header('Location: index.php?action=productos_crear');
            exit();
        }
    }

    // Mostrar formulario editar
    public function editar() {
        Auth::soloAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $producto = $this->model->obtenerPorId($id);

        if (!$producto) {
            $_SESSION['error'] = 'Producto no encontrado';
            header('Location: index.php?action=productos');
            exit();
        }

        $imagenes       = $this->model->obtenerImagenes($id) ?? [];
        $categorias     = $this->model->obtenerCategorias() ?? [];
        $referencia     = $this->model->obtenerReferencia($id) ?? [];
        $compatibilidades = $this->model->obtenerTodasCompatibilidades($id);

        ob_start();
        require_once __DIR__ . '/../views/admin/productos/editar.php';
        $contenido = ob_get_clean();

        require_once __DIR__ . '/../views/admin/nav.php';
    }

    // Actualizar producto
    public function actualizar() {
        Auth::soloAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $id = (int) ($_POST['id_producto'] ?? 0);

        try {
            $datos = [
                'nombre'       => trim($_POST['nombre'] ?? ''),
                'codigo'       => trim($_POST['codigo'] ?? ''),
                'descripcion'  => trim($_POST['descripcion'] ?? ''),
                'precio'       => $_POST['precio'] ?? '0',
                'estado'       => $_POST['estado'] ?? '1',
                'id_categoria' => $_POST['id_categoria'] ?? 0,
            ];

            // 1. Actualizar PRODUCTO
            $this->model->actualizar($id, $datos);

            // 2. Upsert REFERENCIA_PRODUCTO (crear si no existe, actualizar si ya existe)
            $refDatos = [
                'numero_referencia' => trim($_POST['numero_referencia'] ?? ''),
                'marca'             => trim($_POST['ref_marca'] ?? ''),
                'fabricante'        => trim($_POST['fabricante'] ?? ''),
                'especificaciones'  => trim($_POST['especificaciones'] ?? ''),
            ];
            $referencia = $this->model->obtenerReferencia($id);
            if (empty($referencia)) {
                $idReferencia = $this->model->crearReferencia($id, $refDatos);
            } else {
                $this->model->actualizarReferencia($id, $refDatos);
                $idReferencia = (int)($referencia['id_referencia'] ?? 0);
            }

            // 3. Reemplazar compatibilidades: borrar todo y re-insertar

            if ($idReferencia > 0) {
                $this->model->eliminarCompatibilidadesPorReferencia($idReferencia);

                foreach ($_POST['vehiculos'] ?? [] as $v) {
                    if (empty(trim($v['marca_vehiculo'] ?? '')) || empty(trim($v['modelo_vehiculo'] ?? ''))) continue;
                    $this->model->crearCompatibilidadVehiculo($idReferencia, $v);
                }
                foreach ($_POST['maquinaria'] ?? [] as $m) {
                    if (empty(trim($m['marca_maquinaria'] ?? '')) || empty(trim($m['modelo_maquinaria'] ?? ''))) continue;
                    $this->model->crearCompatibilidadMaquinaria($idReferencia, $m);
                }
            }

            $this->model->commit();

            // 4. Imágenes nuevas (fuera de la transacción Oracle)
            if (isset($_FILES['imagenes']) && !empty($_FILES['imagenes']['name'][0])) {
                $this->guardarImagenes($id, $_FILES['imagenes']);
            }

            $this->invalidarCacheCatalogo();
            $_SESSION['success'] = 'Producto actualizado exitosamente';
            header('Location: index.php?action=productos');
            exit();

        } catch (Exception $e) {
            $this->model->rollback();
            error_log('Error al actualizar producto: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al actualizar el producto: ' . $e->getMessage();
            header('Location: index.php?action=productos_editar&id=' . $id);
            exit();
        }
    }
    // Ver detalle del producto
    public function ver() {
        Auth::soloAdmin();
        $id = $_GET['id'] ?? 0;
        $producto = $this->model->obtenerPorId($id);

        if (!$producto) {
            $_SESSION['error'] = 'Producto no encontrado';
            header('Location: index.php?action=productos');
            exit();
        }

        $imagenes = $this->model->obtenerImagenes($id) ?? [];

        ob_start();
        require_once __DIR__ . '/../views/admin/productos/ver.php';
        $contenido = ob_get_clean();

        require_once __DIR__ . '/../views/admin/nav.php';
    }
    // Eliminar producto COMPLETO (con todas sus imágenes)
    public function eliminar() {
        Auth::soloAdmin();
        $id = (int) ($_GET['id'] ?? 0);

        try {
            // Intentar borrar primero (falla rápido si hay FK violation)
            $this->model->eliminar($id);

            // Solo si el DELETE fue exitoso, borramos archivos e imágenes
            $imagenes = $this->model->obtenerImagenes($id);
            foreach ($imagenes as $img) {
                $rutaRelativa = str_replace('/uploads/', '', $img['url']);
                $rutaAbsoluta = UploadHelper::getBasePath() . $rutaRelativa;
                if (file_exists($rutaAbsoluta)) {
                    unlink($rutaAbsoluta);
                }
            }
            $this->model->eliminarImagenes($id);

            $this->invalidarCacheCatalogo();
            $_SESSION['success'] = 'Producto eliminado exitosamente';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: index.php?action=productos');
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
        
        $this->invalidarCacheCatalogo();
        $_SESSION['success'] = "Imagen eliminada";
        header("Location: index.php?action=productos_editar&id=" . $id_producto);
        exit();
    }

    private function guardarImagenes($id_producto, $archivos) {
    // Obtener el orden actual de las imágenes existentes
    $imagenesExistentes = $this->model->obtenerImagenes($id_producto);
    $orden = count($imagenesExistentes);
    
    foreach ($archivos['tmp_name'] as $key => $tmp_name) {
        if ($archivos['error'][$key] === 0) {
            $archivo = [
                'name' => $archivos['name'][$key],
                'type' => $archivos['type'][$key],
                'tmp_name' => $archivos['tmp_name'][$key],
                'size' => $archivos['size'][$key]
            ];
            
            try {
                // Usar UploadHelper para procesar y guardar la imagen
                $rutaRelativa = UploadHelper::procesarImagen($archivo, $id_producto, $orden);
                $this->model->guardarImagen($id_producto, $rutaRelativa, $orden);
                $orden++;
            } catch (Exception $e) {
                error_log("Error al guardar imagen: " . $e->getMessage());
                $_SESSION['error'] = $e->getMessage();
            }
        }
    }
}
}
?>
