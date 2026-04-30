<?php
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../config/database.php';

class TiendaController {

    private $model;
    private $carritoModel;
    private const CACHE_TTL = 60;

    public function __construct() {
    }

    private function productoModel(): ProductoModel {
        if (!$this->model) {
            $this->model = new ProductoModel(Database::getConnection());
        }

        return $this->model;
    }

    private function carritoModel(): CarritoModel {
        if (!$this->carritoModel) {
            $this->carritoModel = new CarritoModel(Database::getConnection());
        }

        return $this->carritoModel;
    }

    private function getCachedCart(): ?array {
        $cache = $_SESSION['carrito_mapa_cache'] ?? null;
        if (
            is_array($cache)
            && isset($cache['expires'], $cache['data'])
            && $cache['expires'] >= time()
            && is_array($cache['data'])
        ) {
            return $cache['data'];
        }

        return null;
    }

    private function setCachedCart(array $carrito): void {
        $_SESSION['carrito_mapa_cache'] = [
            'expires' => time() + 30,
            'data' => $carrito
        ];
    }

    private function obtenerCarritoVista() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!empty($_SESSION['logueado']) && isset($_SESSION['id_usuario'])) {
            unset($_SESSION['carrito']);
            $carrito = $this->getCachedCart();
            if ($carrito === null) {
                $carrito = $this->carritoModel()->obtenerMapaCarritoUsuario((int) $_SESSION['id_usuario']);
                $this->setCachedCart($carrito);
            }
            $_SESSION['carrito_count'] = array_sum($carrito);
            return $carrito;
        }

        $_SESSION['carrito_count'] = 0;
        return [];
    }

    private function getCache(string $key) {
        $cache = $_SESSION['tienda_cache'][$key] ?? null;
        if (
            is_array($cache)
            && isset($cache['expires'], $cache['data'])
            && $cache['expires'] >= time()
        ) {
            return $cache['data'];
        }

        return null;
    }

    private function setCache(string $key, $data): void {
        $_SESSION['tienda_cache'][$key] = [
            'expires' => time() + self::CACHE_TTL,
            'data' => $data
        ];
    }

    private function obtenerCatalogoCacheado(): array {
        $productos = $this->getCache('catalogo');
        if ($productos !== null) {
            return $productos;
        }

        $productos = $this->productoModel()->obtenerCatalogo();
        $this->setCache('catalogo', $productos);
        return $productos;
    }

    private function obtenerCategoriasCacheadas(): array {
        $categorias = $this->getCache('categorias');
        if ($categorias !== null) {
            return $categorias;
        }

        $categorias = $this->productoModel()->obtenerCategorias();
        $this->setCache('categorias', $categorias);
        return $categorias;
    }

    private function obtenerMasVendidosCacheados(): array {
        $masVendidos = $this->getCache('mas_vendidos');
        if ($masVendidos !== null) {
            return $masVendidos;
        }

        $masVendidos = $this->productoModel()->obtenerMasVendidos(5);
        $this->setCache('mas_vendidos', $masVendidos);
        return $masVendidos;
    }

    // Ã°Å¸â€ºÂÃ¯Â¸Â CATÃƒÂLOGO
    public function inicio() {
        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);
        $masVendidos = $this->obtenerMasVendidosCacheados();

        require_once __DIR__ . '/../views/Inicio.php';
    }

    public function index() {
        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);

        $filtro = $_GET['filtro'] ?? '';
        $precio_min = preg_replace('/\D/', '', $_GET['precio_min'] ?? '');
        $precio_max = preg_replace('/\D/', '', $_GET['precio_max'] ?? '');
        $categoria_filtro = $_GET['categoria'] ?? '';

        $productos = $this->obtenerCatalogoCacheado();
        $masVendidos = empty($filtro) && empty($precio_min) && empty($precio_max) && empty($categoria_filtro)
            ? $this->obtenerMasVendidosCacheados()
            : [];

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
                $match_precio = $p['precio'] >= (int) $precio_min;
            }

            if ($precio_max !== '') {
                $match_precio = $match_precio && $p['precio'] <= (int) $precio_max;
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
        }, $this->obtenerCategoriasCacheadas());

        foreach ($productos as $p) {
            $cat = $p['categoria_nombre'] ?? 'Sin categoria';
            $categorias[$cat][] = $p;
        }

        require_once __DIR__ . '/../views/Tienda.php';
    }

    // Ã°Å¸â€Â DETALLE
    public function detalle() {
        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);

        $id = $_GET['id'] ?? 0;

        $producto = $this->productoModel()->obtenerPorId($id);
        if (!$producto) {
            header("Location: index.php?action=tienda");
            exit();
        }

        $imagenes = $this->productoModel()->obtenerImagenes($id);

        require_once __DIR__ . '/../views/tienda/detalle.php';
    }
}
