public function index() {

    $filtro = $_GET['filtro'] ?? '';
    $precio_min = $_GET['precio_min'] ?? '';
    $precio_max = $_GET['precio_max'] ?? '';
    $categoria_filtro = $_GET['categoria'] ?? '';

    $productos = $this->model->obtenerCatalogo();

    $productos = array_filter($productos, function($p) use ($filtro, $precio_min, $precio_max, $categoria_filtro) {

        // 🔍 TEXTO
        $match_texto = true;
        if (!empty($filtro)) {
            $f = strtolower($filtro);

            $match_texto =
                str_contains(strtolower($p['nombre']), $f) ||
                str_contains(strtolower($p['codigo']), $f) ||
                str_contains(strtolower($p['descripcion']), $f);
        }

        // 💰 PRECIO
        $match_precio = true;

        if ($precio_min !== '') {
            $match_precio = $p['precio'] >= $precio_min;
        }

        if ($precio_max !== '') {
            $match_precio = $match_precio && $p['precio'] <= $precio_max;
        }

        // 📦 CATEGORIA
        $match_categoria = true;
        if (!empty($categoria_filtro)) {
            $match_categoria = $p['categoria_nombre'] === $categoria_filtro;
        }

        return $match_texto && $match_precio && $match_categoria;

    });

    // 🔥 AGRUPAR
    $categorias = [];

    foreach ($productos as $p) {
        $cat = $p['categoria_nombre'] ?? 'Sin categoría';
        $categorias[$cat][] = $p;
    }

    require_once __DIR__ . '/../views/Tienda.php';
}