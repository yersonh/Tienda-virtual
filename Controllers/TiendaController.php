<?php
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../config/database.php';

class TiendaController {

    private $model;

    public function __construct() {
        $pdo = Database::getConnection();
        $this->model = new ProductoModel($pdo);
    }

    // 🛍️ CATÁLOGO
    public function index() {

        $productos = $this->model->obtenerCatalogo();

        // AGRUPAR POR CATEGORIA
        $categorias = [];

        foreach ($productos as $p) {
            $categorias[$p['categoria_nombre']][] = $p;
        }

        require_once __DIR__ . '/../views/tienda/productos.php';
    }

    // 🔍 DETALLE
    public function detalle() {

        $id = $_GET['id'] ?? 0;

        $producto = $this->model->obtenerPorId($id);
        $imagenes = $this->model->obtenerImagenes($id);

        require_once __DIR__ . '/../views/tienda/detalle.php';
    }
}