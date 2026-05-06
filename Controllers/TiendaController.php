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
        $cacheKey = $incluirDescripcion ? 'catalogo_full_compat_v2' : 'catalogo_ligero_compat_v2';
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

    private function filtrarProductosGenerales(array $productos, string $filtro, string $precio_min, string $precio_max, string $categoria_filtro): array {
        return array_values(array_filter($productos, function($p) use ($filtro, $precio_min, $precio_max, $categoria_filtro) {
            $match_texto = true;
            if ($filtro !== '') {
                $f = strtolower($filtro);
                $match_texto =
                    str_contains(strtolower((string) ($p['nombre'] ?? '')), $f) ||
                    str_contains(strtolower((string) ($p['codigo'] ?? '')), $f) ||
                    str_contains(strtolower((string) ($p['descripcion'] ?? '')), $f);
            }

            $match_precio = true;
            if ($precio_min !== '') {
                $match_precio = (int) ($p['precio'] ?? 0) >= (int) $precio_min;
            }
            if ($precio_max !== '') {
                $match_precio = $match_precio && (int) ($p['precio'] ?? 0) <= (int) $precio_max;
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

    private function construirOpcionesCompatibilidadVehiculo(array $productos, array $marcasSeleccionadas, array $modelosSeleccionados, array $anosSeleccionados): array {
        $marcas = [];
        $modelos = [];
        $anos = [];

        foreach ($productos as $producto) {
            foreach (($producto['compatibilidades']['vehiculos'] ?? []) as $vehiculo) {
                if ($this->vehiculoCumple($vehiculo, [], $modelosSeleccionados, $anosSeleccionados)) {
                    $this->agregarOpcion($marcas, $vehiculo['marca_vehiculo'] ?? '');
                }
                if ($this->vehiculoCumple($vehiculo, $marcasSeleccionadas, [], $anosSeleccionados)) {
                    $this->agregarOpcion($modelos, $vehiculo['modelo_vehiculo'] ?? '');
                }
                if ($this->vehiculoCumple($vehiculo, $marcasSeleccionadas, $modelosSeleccionados, [])) {
                    $inicio = (int) ($vehiculo['ano_inicio'] ?? 0);
                    $fin = (int) ($vehiculo['ano_fin'] ?? 0);
                    if ($inicio > 0 && $fin >= $inicio && ($fin - $inicio) <= 120) {
                        foreach (range($inicio, $fin) as $ano) {
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
                if ($this->maquinariaCumple($maquinaria, [], $marcasSeleccionadas, $modelosSeleccionados)) {
                    $this->agregarOpcion($tipos, $maquinaria['tipo_maquinaria'] ?? '');
                }
                if ($this->maquinariaCumple($maquinaria, $tiposSeleccionados, [], $modelosSeleccionados)) {
                    $this->agregarOpcion($marcas, $maquinaria['marca_maquinaria'] ?? '');
                }
                if ($this->maquinariaCumple($maquinaria, $tiposSeleccionados, $marcasSeleccionadas, [])) {
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

    private function mezclarOpcionesSeleccionadas(array $opciones, array $seleccionadas, bool $numeric = false): array {
        foreach ($seleccionadas as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $opciones[$value] = $value;
            }
        }

        if ($numeric) {
            ksort($opciones, SORT_NUMERIC);
        } else {
            natcasesort($opciones);
        }

        return array_values($opciones);
    }

    private function conservarSeleccionesEnOpcionesVehiculo(array $opciones, array $marcas, array $modelos, array $anos): array {
        return [
            'marcas' => $this->mezclarOpcionesSeleccionadas(array_combine($opciones['marcas'] ?? [], $opciones['marcas'] ?? []) ?: [], $marcas),
            'modelos' => $this->mezclarOpcionesSeleccionadas(array_combine($opciones['modelos'] ?? [], $opciones['modelos'] ?? []) ?: [], $modelos),
            'anos' => $this->mezclarOpcionesSeleccionadas(array_combine(array_map('strval', $opciones['anos'] ?? []), array_map('strval', $opciones['anos'] ?? [])) ?: [], array_map('strval', $anos), true)
        ];
    }

    private function conservarSeleccionesEnOpcionesMaquinaria(array $opciones, array $tipos, array $marcas, array $modelos): array {
        return [
            'tipos' => $this->mezclarOpcionesSeleccionadas(array_combine($opciones['tipos'] ?? [], $opciones['tipos'] ?? []) ?: [], $tipos),
            'marcas' => $this->mezclarOpcionesSeleccionadas(array_combine($opciones['marcas'] ?? [], $opciones['marcas'] ?? []) ?: [], $marcas),
            'modelos' => $this->mezclarOpcionesSeleccionadas(array_combine($opciones['modelos'] ?? [], $opciones['modelos'] ?? []) ?: [], $modelos)
        ];
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

        $productosBase = $this->obtenerCatalogoCacheado(!empty($filtro));
        $productosFiltradosBase = $this->filtrarProductosGenerales($productosBase, trim((string) $filtro), $precio_min, $precio_max, trim((string) $categoria_filtro));

        $opcionesVehiculo = ($incluirOpcionesVehiculo || $compatibilidad_tipo === 'vehiculo')
            ? $this->construirOpcionesCompatibilidadVehiculo($productosFiltradosBase, $vehiculo_marcas, $vehiculo_modelos, $vehiculo_anos)
            : ['marcas' => [], 'modelos' => [], 'anos' => []];
        $opcionesMaquinaria = $compatibilidad_tipo === 'maquinaria'
            ? $this->construirOpcionesCompatibilidadMaquinaria($productosFiltradosBase, $maquinaria_tipos, $maquinaria_marcas, $maquinaria_modelos)
            : ['tipos' => [], 'marcas' => [], 'modelos' => []];

        $opcionesVehiculo = $this->conservarSeleccionesEnOpcionesVehiculo($opcionesVehiculo, $vehiculo_marcas, $vehiculo_modelos, $vehiculo_anos);
        $opcionesMaquinaria = $this->conservarSeleccionesEnOpcionesMaquinaria($opcionesMaquinaria, $maquinaria_tipos, $maquinaria_marcas, $maquinaria_modelos);

        $productos = $productosFiltradosBase;
        if (($compatibilidad_tipo === 'vehiculo' && $hayFiltroVehiculo) || ($compatibilidad_tipo === 'maquinaria' && $hayFiltroMaquinaria)) {
            $compatibilidad_activa = true;
            $productos = $this->filtrarPorCompatibilidad(
                $productosFiltradosBase,
                $compatibilidad_tipo,
                $vehiculo_marcas,
                $vehiculo_modelos,
                $vehiculo_anos,
                $maquinaria_tipos,
                $maquinaria_marcas,
                $maquinaria_modelos
            );
        }

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
