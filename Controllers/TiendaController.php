<?php
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../config/database.php';

class TiendaController {

    private $model;
    private $carritoModel;

    public function __construct() {
        $pdo = Database::getConnection();
        $this->model = new ProductoModel($pdo);
        $this->carritoModel = new CarritoModel($pdo);
    }

    private function syncCartSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['id_usuario'])) {
            $_SESSION['carrito'] = $this->carritoModel->obtenerMapaCarritoUsuario((int) $_SESSION['id_usuario']);
        } elseif (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
    }

    // Ã°Å¸â€ºÂÃ¯Â¸Â CATÃƒÂLOGO
    public function index() {
        $this->syncCartSession();

        $filtro = $_GET['filtro'] ?? '';
        $precio_min = $_GET['precio_min'] ?? '';
        $precio_max = $_GET['precio_max'] ?? '';
        $categoria_filtro = $_GET['categoria'] ?? '';
        $proveedor_filtro = $_GET['proveedor'] ?? '';

        $productos = $this->model->obtenerCatalogo();

        $productos = array_filter($productos, function($p) use ($filtro, $precio_min, $precio_max, $categoria_filtro) {

            // Ã°Å¸â€Â TEXTO
            $match_texto = true;
            if (!empty($filtro)) {
                $f = strtolower($filtro);

                $match_texto =
                    str_contains(strtolower($p['nombre']), $f) ||
                    str_contains(strtolower($p['codigo']), $f) ||
                    str_contains(strtolower($p['descripcion']), $f);
            }

            // Ã°Å¸â€™Â° PRECIO
            $match_precio = true;

           if ($precio_min !== '') {
                $match_precio = $p['precio'] >= str_replace('.', '', $precio_min);
            }

            if ($precio_max !== '') {
                $match_precio = $match_precio && $p['precio'] <= str_replace('.', '', $precio_max);
            }

            // Ã°Å¸â€œÂ¦ CATEGORIA
            $match_categoria = true;
            if (!empty($categoria_filtro)) {
                $match_categoria = $p['categoria_nombre'] === $categoria_filtro;
            }

            return $match_texto && $match_precio && $match_categoria;
        });

        // Ã°Å¸â€Â¥ AGRUPAR
        $categorias = [];
        $todasCategorias = array_map(function($cat) {
            return $cat['nombre'];
        }, $this->model->obtenerCategorias());

        foreach ($productos as $p) {
            $cat = $p['categoria_nombre'] ?? 'Sin categoria';
            $categorias[$cat][] = $p;
        }

        require_once __DIR__ . '/../views/Tienda.php';
    }

    // Ã°Å¸â€Â DETALLE
    public function detalle() {
        $this->syncCartSession();

        $id = $_GET['id'] ?? 0;

        $producto = $this->model->obtenerPorId($id);
        if (!$producto) {
            header("Location: index.php?action=tienda");
            exit();
        }

        $imagenes = $this->model->obtenerImagenes($id);

        require_once __DIR__ . '/../views/tienda/detalle.php';
    }
}
