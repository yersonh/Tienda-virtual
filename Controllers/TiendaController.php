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

    private function obtenerCatalogoCacheado(bool $incluirDescripcion = true): array {
        $cacheKey = $incluirDescripcion ? 'catalogo_full_compat' : 'catalogo_ligero_compat';
        $productos = $this->getCache($cacheKey);
        if ($productos !== null) {
            return $productos;
        }

        $productos = $this->productoModel()->obtenerCatalogo($incluirDescripcion);
        $this->setCache($cacheKey, $productos);
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

        $masVendidos = $this->productoModel()->obtenerMasVendidos(10);
        $this->setCache('mas_vendidos', $masVendidos);
        return $masVendidos;
    }

    // Ã°Å¸â€ºÂÃ¯Â¸Â CATÃƒÂLOGO
    private function obtenerProductosNuevosCacheados(): array {
        $productosNuevos = $this->getCache('productos_nuevos');
        if ($productosNuevos !== null) {
            return $productosNuevos;
        }

        $productosNuevos = $this->productoModel()->obtenerProductosNuevos(10);
        $this->setCache('productos_nuevos', $productosNuevos);
        return $productosNuevos;
    }

    private function obtenerValoresGet(string $key, bool $soloNumeros = false): array {
        $value = $_GET[$key] ?? [];
        $values = is_array($value) ? $value : [$value];
        $clean = [];

        foreach ($values as $item) {
            $item = $soloNumeros ? preg_replace('/\D/', '', (string) $item) : trim((string) $item);
            if ($item !== '') {
                $clean[] = $soloNumeros ? (int) $item : $item;
            }
        }

        return array_values(array_unique($clean));
    }

    private function obtenerDatosTienda(bool $incluirOpcionesVehiculo = true): array {
        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);

        $filtro = $_GET['filtro'] ?? '';
        $precio_min = preg_replace('/\D/', '', $_GET['precio_min'] ?? '');
        $precio_max = preg_replace('/\D/', '', $_GET['precio_max'] ?? '');
        $categoria_filtro = $_GET['categoria'] ?? '';
        $compatibilidad_tipo = $_GET['compatibilidad_tipo'] ?? '';
        $compatibilidad_tipo = in_array($compatibilidad_tipo, ['vehiculo', 'maquinaria'], true) ? $compatibilidad_tipo : '';
        $vehiculo_marcas = $this->obtenerValoresGet('vehiculo_marca');
        $vehiculo_modelos = $this->obtenerValoresGet('vehiculo_modelo');
        $vehiculo_anos = $this->obtenerValoresGet('vehiculo_ano', true);
        $maquinaria_tipos = array_merge($this->obtenerValoresGet('tipo'), $this->obtenerValoresGet('maquinaria_tipo'));
        $maquinaria_marcas = array_merge($this->obtenerValoresGet('marca'), $this->obtenerValoresGet('maquinaria_marca'));
        $maquinaria_modelos = array_merge($this->obtenerValoresGet('modelo'), $this->obtenerValoresGet('maquinaria_modelo'));
        $maquinaria_tipos = array_values(array_unique($maquinaria_tipos));
        $maquinaria_marcas = array_values(array_unique($maquinaria_marcas));
        $maquinaria_modelos = array_values(array_unique($maquinaria_modelos));
        $hayFiltroVehiculo = !empty($vehiculo_marcas) || !empty($vehiculo_modelos) || !empty($vehiculo_anos);
        $hayFiltroMaquinaria = !empty($maquinaria_tipos) || !empty($maquinaria_marcas) || !empty($maquinaria_modelos);
        $compatibilidad_activa = false;

        if ($compatibilidad_tipo === '' && $hayFiltroVehiculo) {
            $compatibilidad_tipo = 'vehiculo';
        }
        if ($compatibilidad_tipo === '' && $hayFiltroMaquinaria) {
            $compatibilidad_tipo = 'maquinaria';
        }

        $opcionesVehiculo = ($incluirOpcionesVehiculo || $compatibilidad_tipo === 'vehiculo')
            ? $this->productoModel()->obtenerOpcionesCompatibilidadVehiculo()
            : ['marcas' => [], 'modelos' => [], 'anos' => []];
        $opcionesMaquinaria = $compatibilidad_tipo === 'maquinaria'
            ? [
                'tipos' => $this->productoModel()->obtenerTipos($maquinaria_marcas, $maquinaria_modelos),
                'marcas' => $this->productoModel()->obtenerMarcas($maquinaria_modelos, $maquinaria_tipos),
                'modelos' => $this->productoModel()->obtenerModelos($maquinaria_marcas, $maquinaria_tipos)
            ]
            : ['tipos' => [], 'marcas' => [], 'modelos' => []];

        if ($compatibilidad_tipo === 'vehiculo' && $hayFiltroVehiculo) {
            $compatibilidad_activa = true;
            $productos = $this->productoModel()->filtrarVehiculo($vehiculo_marcas, $vehiculo_modelos, $vehiculo_anos);
        } elseif ($compatibilidad_tipo === 'maquinaria' && $hayFiltroMaquinaria) {
            $compatibilidad_activa = true;
            $productos = $this->productoModel()->filtrarProductos($maquinaria_marcas, $maquinaria_modelos, $maquinaria_tipos);
        } else {
            $productos = $this->obtenerCatalogoCacheado(!empty($filtro));
        }

        $productos = array_filter($productos, function($p) use ($filtro, $precio_min, $precio_max, $categoria_filtro) {
            $match_texto = true;
            if (!empty($filtro)) {
                $f = strtolower($filtro);
                $match_texto =
                    str_contains(strtolower((string)$p['nombre']), $f) ||
                    str_contains(strtolower((string)$p['codigo']), $f) ||
                    str_contains(strtolower((string)($p['descripcion'] ?? '')), $f);
            }

            $match_precio = true;
            if ($precio_min !== '') {
                $match_precio = (int)$p['precio'] >= (int)$precio_min;
            }
            if ($precio_max !== '') {
                $match_precio = $match_precio && (int)$p['precio'] <= (int)$precio_max;
            }

            $match_categoria = true;
            if (!empty($categoria_filtro)) {
                $match_categoria = strtolower(trim((string)$p['categoria_nombre'])) === strtolower(trim((string)$categoria_filtro));
            }

            return $match_texto && $match_precio && $match_categoria;
        });

        $categorias = [];
        $todasCategorias = array_map(function($cat) {
            return $cat['nombre'];
        }, $this->obtenerCategoriasCacheadas() ?? []);

        foreach ($productos as $p) {
            $cat = $p['categoria_nombre'] ?? 'Sin categoria';
            $categorias[$cat][] = $p;
        }

        return compact(
            'carritoVista',
            'carritoCount',
            'filtro',
            'precio_min',
            'precio_max',
            'categoria_filtro',
            'compatibilidad_tipo',
            'compatibilidad_activa',
            'vehiculo_marcas',
            'vehiculo_modelos',
            'vehiculo_anos',
            'maquinaria_tipos',
            'maquinaria_marcas',
            'maquinaria_modelos',
            'opcionesVehiculo',
            'opcionesMaquinaria',
            'categorias',
            'todasCategorias'
        );
    }

    public function inicio() {
        unset($_SESSION['tienda_cache']['mas_vendidos'], $_SESSION['tienda_cache']['productos_nuevos']);
        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);
        $masVendidos = $this->obtenerMasVendidosCacheados();
        $productosNuevos = $this->obtenerProductosNuevosCacheados();

        require_once __DIR__ . '/../views/Inicio.php';
    }

    public function index() {
        extract($this->obtenerDatosTienda());
        require_once __DIR__ . '/../views/Tienda.php';
        return;

        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);

        $filtro = $_GET['filtro'] ?? '';
        $precio_min = preg_replace('/\D/', '', $_GET['precio_min'] ?? '');
        $precio_max = preg_replace('/\D/', '', $_GET['precio_max'] ?? '');
        $categoria_filtro = $_GET['categoria'] ?? '';
        $compatibilidad_tipo = $_GET['compatibilidad_tipo'] ?? '';
        $compatibilidad_tipo = in_array($compatibilidad_tipo, ['vehiculo', 'maquinaria'], true) ? $compatibilidad_tipo : '';
        $vehiculo_marcas = $this->obtenerValoresGet('vehiculo_marca');
        $vehiculo_modelos = $this->obtenerValoresGet('vehiculo_modelo');
        $vehiculo_anos = $this->obtenerValoresGet('vehiculo_ano', true);
        $maquinaria_tipos = array_merge(
            $this->obtenerValoresGet('tipo'),
            $this->obtenerValoresGet('maquinaria_tipo')
        );
        $maquinaria_marcas = array_merge(
            $this->obtenerValoresGet('marca'),
            $this->obtenerValoresGet('maquinaria_marca')
        );
        $maquinaria_modelos = array_merge(
            $this->obtenerValoresGet('modelo'),
            $this->obtenerValoresGet('maquinaria_modelo')
        );
        $maquinaria_tipos = array_values(array_unique($maquinaria_tipos));
        $maquinaria_marcas = array_values(array_unique($maquinaria_marcas));
        $maquinaria_modelos = array_values(array_unique($maquinaria_modelos));
        if ($compatibilidad_tipo === '' && (!empty($maquinaria_tipos) || !empty($maquinaria_marcas) || !empty($maquinaria_modelos))) {
            $compatibilidad_tipo = 'maquinaria';
        }
        $opcionesVehiculo = $this->productoModel()->obtenerOpcionesCompatibilidadVehiculo();
        $opcionesMaquinaria = [
            'tipos' => $this->productoModel()->obtenerTipos($maquinaria_marcas, $maquinaria_modelos),
            'marcas' => $this->productoModel()->obtenerMarcas($maquinaria_modelos, $maquinaria_tipos),
            'modelos' => $this->productoModel()->obtenerModelos($maquinaria_marcas, $maquinaria_tipos)
        ];

        if ($compatibilidad_tipo === 'vehiculo') {
            $productos = $this->productoModel()->filtrarVehiculo(
                $vehiculo_marcas,
                $vehiculo_modelos,
                $vehiculo_anos
            );
        } elseif ($compatibilidad_tipo === 'maquinaria') {
            $productos = $this->productoModel()->filtrarProductos(
                $maquinaria_marcas,
                $maquinaria_modelos,
                $maquinaria_tipos
            );
        } else {
            $productos = $this->obtenerCatalogoCacheado(!empty($filtro));
        }

        $productos = array_filter($productos, function($p) use ($filtro, $precio_min, $precio_max, $categoria_filtro) {

            $match_texto = true;
            if (!empty($filtro)) {
                $f = strtolower($filtro);

                $match_texto =
                    str_contains(strtolower((string)$p['nombre']), $f) ||
                    str_contains(strtolower((string)$p['codigo']), $f) ||
                    str_contains(strtolower((string)($p['descripcion'] ?? '')), $f);
            }

            $match_precio = true;

            if ($precio_min !== '') {
                $match_precio = (int)$p['precio'] >= (int)$precio_min;
            }

            if ($precio_max !== '') {
                $match_precio = $match_precio && (int)$p['precio'] <= (int)$precio_max;
            }

            $match_categoria = true;
            if (!empty($categoria_filtro)) {
                $match_categoria =
                    strtolower(trim((string)$p['categoria_nombre'])) ===
                    strtolower(trim((string)$categoria_filtro));
            }

            return $match_texto && $match_precio && $match_categoria;
        });

        // Ã°Å¸â€Â¥ AGRUPAR
        $categorias = [];
        $todasCategorias = array_map(function($cat) {
            return $cat['nombre'];
        }, $this->obtenerCategoriasCacheadas() ?? []);

        foreach ($productos as $p) {
            $cat = $p['categoria_nombre'] ?? 'Sin categoria';
            $categorias[$cat][] = $p;
        }

        require_once __DIR__ . '/../views/Tienda.php';
    }

    // Ã°Å¸â€Â DETALLE
    public function filtrosAjax() {
        $modoCompatibilidad = $_GET['compatibilidad_tipo'] ?? '';
        extract($this->obtenerDatosTienda($modoCompatibilidad === 'vehiculo'));
        $usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);

        ob_start();
        require __DIR__ . '/../views/partials/tienda_productos.php';
        $productosHtml = ob_get_clean();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'productos_html' => $productosHtml,
            'compatibilidad_tipo' => $compatibilidad_tipo,
            'compatibilidad_activa' => $compatibilidad_activa,
            'opciones' => [
                'vehiculo' => $opcionesVehiculo,
                'maquinaria' => $opcionesMaquinaria
            ],
            'seleccion' => [
                'vehiculo' => [
                    'marcas' => $vehiculo_marcas,
                    'modelos' => $vehiculo_modelos,
                    'anos' => array_map('strval', $vehiculo_anos)
                ],
                'maquinaria' => [
                    'tipos' => $maquinaria_tipos,
                    'marcas' => $maquinaria_marcas,
                    'modelos' => $maquinaria_modelos
                ]
            ],
            'categorias' => array_map('count', $categorias)
        ]);
        exit();
    }

    public function detalle() {
        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);
        $carritoItemsResumen = [];

        if (!empty($_SESSION['logueado']) && isset($_SESSION['id_usuario'])) {
            $carritoItemsResumen = $this->carritoModel()->obtenerItemsVisualizacion((int) $_SESSION['id_usuario']);
        }

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
