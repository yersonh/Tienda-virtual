<?php
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../config/database.php';

class TiendaController {

    private $model;
    private $carritoModel;
    private const CACHE_TTL = 120;

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

    private function obtenerCatalogoCacheado(bool $incluirDescripcion = true, bool $forzarActualizacion = false): array {
        $cacheKey = $incluirDescripcion ? 'catalogo_full_compat_v2' : 'catalogo_ligero_compat_v2';
        $productos = $forzarActualizacion ? null : $this->getCache($cacheKey);
        if ($productos !== null) {
            return $productos;
        }

        $productos = $this->productoModel()->obtenerCatalogo($incluirDescripcion);
        $this->setCache($cacheKey, $productos);
        return $productos;
    }

    private function ordenarProductosCategoria(array $productos): array {
        usort($productos, function(array $a, array $b): int {
            $sinStockA = (int) ($a['stock_p'] ?? 0) <= 0 ? 1 : 0;
            $sinStockB = (int) ($b['stock_p'] ?? 0) <= 0 ? 1 : 0;
            if ($sinStockA !== $sinStockB) {
                return $sinStockA <=> $sinStockB;
            }

            return strnatcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
        });

        return $productos;
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

    private function filtrarProductosGenerales(array $productos, string $filtro, string $precio_min, string $precio_max, string $categoria_filtro): array {
        return array_values(array_filter($productos, function($p) use ($filtro, $precio_min, $precio_max, $categoria_filtro) {
            $match_texto = true;
            if ($filtro !== '') {
                $f = function_exists('mb_strtolower') ? mb_strtolower($filtro, 'UTF-8') : strtolower($filtro);
                $nombre = function_exists('mb_strtolower') ? mb_strtolower((string) ($p['nombre'] ?? ''), 'UTF-8') : strtolower((string) ($p['nombre'] ?? ''));
                $codigo = function_exists('mb_strtolower') ? mb_strtolower((string) ($p['codigo'] ?? ''), 'UTF-8') : strtolower((string) ($p['codigo'] ?? ''));
                $descripcion = function_exists('mb_strtolower') ? mb_strtolower((string) ($p['descripcion'] ?? ''), 'UTF-8') : strtolower((string) ($p['descripcion'] ?? ''));
                $match_texto =
                    str_contains($nombre, $f) ||
                    str_contains($codigo, $f) ||
                    str_contains($descripcion, $f);
            }

            $precio = (float) ($p['precio'] ?? 0);
            $match_precio = true;
            if ($precio_min !== '') {
                $match_precio = $precio >= (float) $precio_min;
            }
            if ($precio_max !== '') {
                $match_precio = $match_precio && $precio <= (float) $precio_max;
            }

            $match_categoria = true;
            if ($categoria_filtro !== '') {
                $match_categoria = strtolower(trim((string) ($p['categoria_nombre'] ?? ''))) === strtolower(trim($categoria_filtro));
            }

            return $match_texto && $match_precio && $match_categoria;
        }));
    }

    private function normalizarTexto(string $value): string {
        return strtoupper(trim($value));
    }

    private function valorEnSeleccion(string $value, array $selected): bool {
        if (empty($selected)) {
            return true;
        }

        $normalized = $this->normalizarTexto($value);
        foreach ($selected as $item) {
            if ($normalized === $this->normalizarTexto((string) $item)) {
                return true;
            }
        }

        return false;
    }

    private function vehiculoCumple(array $vehiculo, array $marcas, array $modelos, array $anos): bool {
        if (!$this->valorEnSeleccion((string) ($vehiculo['marca_vehiculo'] ?? ''), $marcas)) {
            return false;
        }
        if (!$this->valorEnSeleccion((string) ($vehiculo['modelo_vehiculo'] ?? ''), $modelos)) {
            return false;
        }
        if (!empty($anos)) {
            $inicio = (int) ($vehiculo['ano_inicio'] ?? 0);
            $fin = (int) ($vehiculo['ano_fin'] ?? 0);
            $coincideAno = false;
            foreach ($anos as $ano) {
                $ano = (int) $ano;
                if ($inicio > 0 && $fin > 0 && $ano >= $inicio && $ano <= $fin) {
                    $coincideAno = true;
                    break;
                }
            }
            if (!$coincideAno) {
                return false;
            }
        }

        return true;
    }

    private function maquinariaCumple(array $maquinaria, array $tipos, array $marcas, array $modelos): bool {
        return $this->valorEnSeleccion((string) ($maquinaria['tipo_maquinaria'] ?? ''), $tipos)
            && $this->valorEnSeleccion((string) ($maquinaria['marca_maquinaria'] ?? ''), $marcas)
            && $this->valorEnSeleccion((string) ($maquinaria['modelo_maquinaria'] ?? ''), $modelos);
    }

    private function filtrarPorCompatibilidad(array $productos, string $compatibilidad_tipo, array $vehiculo_marcas, array $vehiculo_modelos, array $vehiculo_anos, array $maquinaria_tipos, array $maquinaria_marcas, array $maquinaria_modelos): array {
        if ($compatibilidad_tipo === 'vehiculo') {
            return array_values(array_filter($productos, function($producto) use ($vehiculo_marcas, $vehiculo_modelos, $vehiculo_anos) {
                foreach (($producto['compatibilidades']['vehiculos'] ?? []) as $vehiculo) {
                    if ($this->vehiculoCumple($vehiculo, $vehiculo_marcas, $vehiculo_modelos, $vehiculo_anos)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        if ($compatibilidad_tipo === 'maquinaria') {
            return array_values(array_filter($productos, function($producto) use ($maquinaria_tipos, $maquinaria_marcas, $maquinaria_modelos) {
                foreach (($producto['compatibilidades']['maquinarias'] ?? []) as $maquinaria) {
                    if ($this->maquinariaCumple($maquinaria, $maquinaria_tipos, $maquinaria_marcas, $maquinaria_modelos)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        return $productos;
    }

    private function agregarOpcion(array &$values, $value): void {
        $value = trim((string) $value);
        if ($value !== '') {
            $values[$value] = $value;
        }
    }

    private function construirCategoriasDisponibles(array $productos): array {
        $categorias = [];
        foreach ($productos as $producto) {
            $this->agregarOpcion($categorias, $producto['categoria_nombre'] ?? 'Sin categoria');
        }

        natcasesort($categorias);
        return array_values($categorias);
    }

    private function categoriaEnOpciones(string $categoria, array $opciones): bool {
        $categoria = $this->normalizarTexto($categoria);
        foreach ($opciones as $opcion) {
            if ($categoria === $this->normalizarTexto((string) $opcion)) {
                return true;
            }
        }

        return false;
    }

    private function construirRangoPrecios(array $productos): array {
        $precios = [];
        foreach ($productos as $producto) {
            $precios[] = (float) ($producto['precio'] ?? 0);
        }

        if (empty($precios)) {
            return ['min' => null, 'max' => null];
        }

        return [
            'min' => min($precios),
            'max' => max($precios)
        ];
    }

    private function construirOpcionesCompatibilidadVehiculo(array $productos, array $marcasSeleccionadas, array $modelosSeleccionados, array $anosSeleccionados): array {
        $marcas = [];
        $modelos = [];
        $anos = [];

        foreach ($productos as $producto) {
            foreach (($producto['compatibilidades']['vehiculos'] ?? []) as $vehiculo) {
                if (!$this->vehiculoCumple($vehiculo, $marcasSeleccionadas, $modelosSeleccionados, $anosSeleccionados)) {
                    continue;
                }

                $this->agregarOpcion($marcas, $vehiculo['marca_vehiculo'] ?? '');
                $this->agregarOpcion($modelos, $vehiculo['modelo_vehiculo'] ?? '');

                $inicio = (int) ($vehiculo['ano_inicio'] ?? 0);
                $fin = (int) ($vehiculo['ano_fin'] ?? 0);
                if ($inicio > 0 && $fin >= $inicio && ($fin - $inicio) <= 120) {
                    foreach (range($inicio, $fin) as $ano) {
                        if (empty($anosSeleccionados) || in_array($ano, $anosSeleccionados, true)) {
                            $anos[(string) $ano] = (string) $ano;
                        }
                    }
                }
            }
        }

        natcasesort($marcas);
        natcasesort($modelos);
        ksort($anos, SORT_NUMERIC);

        return [
            'marcas' => array_values($marcas),
            'modelos' => array_values($modelos),
            'anos' => array_values($anos)
        ];
    }

    private function construirOpcionesCompatibilidadMaquinaria(array $productos, array $tiposSeleccionados, array $marcasSeleccionadas, array $modelosSeleccionados): array {
        $tipos = [];
        $marcas = [];
        $modelos = [];

        foreach ($productos as $producto) {
            foreach (($producto['compatibilidades']['maquinarias'] ?? []) as $maquinaria) {
                if ($this->maquinariaCumple($maquinaria, $tiposSeleccionados, $marcasSeleccionadas, $modelosSeleccionados)) {
                    $this->agregarOpcion($tipos, $maquinaria['tipo_maquinaria'] ?? '');
                    $this->agregarOpcion($marcas, $maquinaria['marca_maquinaria'] ?? '');
                    $this->agregarOpcion($modelos, $maquinaria['modelo_maquinaria'] ?? '');
                }
            }
        }

        natcasesort($tipos);
        natcasesort($marcas);
        natcasesort($modelos);

        return [
            'tipos' => array_values($tipos),
            'marcas' => array_values($marcas),
            'modelos' => array_values($modelos)
        ];
    }

    private function obtenerDatosTienda(bool $incluirOpcionesVehiculo = true, bool $forzarActualizacionCatalogo = false): array {
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

        $productosResultadoFinal = $this->filtrarProductosGenerales(
            $this->obtenerCatalogoCacheado(!empty($filtro), $forzarActualizacionCatalogo),
            trim((string) $filtro),
            $precio_min,
            $precio_max,
            trim((string) $categoria_filtro)
        );

        if (($compatibilidad_tipo === 'vehiculo' && $hayFiltroVehiculo) || ($compatibilidad_tipo === 'maquinaria' && $hayFiltroMaquinaria)) {
            $compatibilidad_activa = true;
            $productosResultadoFinal = $this->filtrarPorCompatibilidad(
                $productosResultadoFinal,
                $compatibilidad_tipo,
                $vehiculo_marcas,
                $vehiculo_modelos,
                $vehiculo_anos,
                $maquinaria_tipos,
                $maquinaria_marcas,
                $maquinaria_modelos
            );
        }

        $todasCategorias = $this->construirCategoriasDisponibles($productosResultadoFinal);
        $rangoPrecios = $this->construirRangoPrecios($productosResultadoFinal);
        $opcionesVehiculo = $this->construirOpcionesCompatibilidadVehiculo($productosResultadoFinal, $vehiculo_marcas, $vehiculo_modelos, $vehiculo_anos);
        $opcionesMaquinaria = $this->construirOpcionesCompatibilidadMaquinaria($productosResultadoFinal, $maquinaria_tipos, $maquinaria_marcas, $maquinaria_modelos);

        $categoria_filtro = trim((string) $categoria_filtro);
        if ($categoria_filtro !== '' && !$this->categoriaEnOpciones($categoria_filtro, $todasCategorias)) {
            $categoria_filtro = '';
        }

        $categorias = [];

        foreach ($productosResultadoFinal as $p) {
            $cat = $p['categoria_nombre'] ?? 'Sin categoria';
            $categorias[$cat][] = $p;
        }
        foreach ($categorias as &$productosCategoria) {
            $productosCategoria = $this->ordenarProductosCategoria($productosCategoria);
        }
        unset($productosCategoria);

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
            'rangoPrecios',
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

    public function nosotros() {
        $carritoVista = $this->obtenerCarritoVista();
        $carritoCount = array_sum($carritoVista);

        require_once __DIR__ . '/../views/Nosotros.php';
    }

    public function index() {
        extract($this->obtenerDatosTienda());
        require_once __DIR__ . '/../views/Tienda.php';

        // Ã°Å¸â€Â¥ AGRUPAR
    }

    // Ã°Å¸â€Â DETALLE
    public function filtrosAjax() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $modoCompatibilidad = $_GET['compatibilidad_tipo'] ?? '';
            $forzarCatalogo = isset($_GET['_refresh_catalog']);
            extract($this->obtenerDatosTienda($modoCompatibilidad === 'vehiculo', $forzarCatalogo));
            $usuarioLogueado = !empty($_SESSION['logueado']) && isset($_SESSION['id_usuario']);

            ob_start();
            require __DIR__ . '/../views/partials/tienda_productos.php';
            $productosHtml = ob_get_clean();

            echo json_encode([
                'success' => true,
                'productos_html' => $productosHtml,
                'categoria_activa' => $categoria_filtro,
                'categorias_disponibles' => $todasCategorias,
                'rango_precios' => $rangoPrecios,
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
        } catch (Throwable $e) {
            error_log('TiendaController::filtrosAjax: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'No se pudieron aplicar los filtros'
            ]);
        }
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

    public function stockProductoJson(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = (int) ($_GET['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Producto invalido'
                ]);
                exit();
            }

            $producto = $this->productoModel()->obtenerPorId($id);
            if (!$producto) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ]);
                exit();
            }

            echo json_encode([
                'success' => true,
                'id_producto' => (int) ($producto['id_producto'] ?? $id),
                'id_referencia' => (int) ($producto['id_referencia'] ?? 0),
                'stock' => max(0, (int) ($producto['stock_p'] ?? 0)),
                'updated_at' => time()
            ]);
        } catch (Throwable $e) {
            error_log('TiendaController::stockProductoJson: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo consultar el stock'
            ]);
        }
        exit();
    }
}
