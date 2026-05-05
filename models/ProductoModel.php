<?php
class ProductoModel {

    private $conn;
    private $dynamicBindValues = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function normalizeValue($value) {
        if ($value instanceof OCILob) {
            $contents = $value->load();
            $value->free();
            return $contents === false ? '' : $contents;
        }

        return $value;
    }

    private function normalizeRow(array $row): array {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[strtolower($key)] = $this->normalizeValue($value);
        }

        return $normalized;
    }

    private function productoColumns(
        string $alias = 'p',
        bool $includeTotalVendido = false,
        string $categoriaAlias = 'c',
        string $imagenAlias = 'img',
        bool $includeDescripcion = true,
        string $referenciaAlias = 'r',
        string $stockAlias = 'stk'
    ): string {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $categoriaPrefix = $categoriaAlias !== '' ? $categoriaAlias . '.' : '';
        $imagenPrefix = $imagenAlias !== '' ? $imagenAlias . '.' : '';
        $referenciaPrefix = $referenciaAlias !== '' ? $referenciaAlias . '.' : '';
        $stockPrefix = $stockAlias !== '' ? $stockAlias . '.' : '';
        $columns = [
            "{$prefix}id_producto",
            "{$referenciaPrefix}id_referencia",
            "{$referenciaPrefix}numero_referencia",
            "{$referenciaPrefix}marca",
            "{$referenciaPrefix}fabricante",
            "{$prefix}nombre",
            "{$prefix}codigo",
            "{$prefix}precio",
            "NVL({$stockPrefix}stock_p, 0) AS stock_p",
            "{$prefix}estado",
            "{$prefix}id_categoria",
            "{$categoriaPrefix}nombre AS categoria_nombre",
            "{$imagenPrefix}imagen AS imagen"
        ];

        if ($includeDescripcion) {
            array_splice($columns, 3, 0, "{$prefix}descripcion");
        }

        return implode(",\n                         ", $columns);
    }

    private function referenciaJoin(): string {
        return "LEFT JOIN (
                    SELECT id_producto,
                        MIN(id_referencia) KEEP (DENSE_RANK FIRST ORDER BY id_referencia) AS id_referencia,
                        MIN(numero_referencia) KEEP (DENSE_RANK FIRST ORDER BY id_referencia) AS numero_referencia,
                        MIN(marca) KEEP (DENSE_RANK FIRST ORDER BY id_referencia) AS marca,
                        MIN(fabricante) KEEP (DENSE_RANK FIRST ORDER BY id_referencia) AS fabricante
                    FROM referencia_producto
                    GROUP BY id_producto
                ) r ON r.id_producto = p.id_producto";
    }

    private function stockReferenciaJoin(string $alias = 'stk'): string {
        return "LEFT JOIN (
                    SELECT x.id_referencia,
                           SUM(x.stock_p) AS stock_p
                    FROM (
                        SELECT cv.id_referencia,
                               NVL(cv.stock_p, 0) AS stock_p
                        FROM compatibilidad_vehiculo cv
                        UNION ALL
                        SELECT cm.id_referencia,
                               NVL(cm.stock_p, 0) AS stock_p
                        FROM compatibilidad_maquinaria cm
                    ) x
                    GROUP BY x.id_referencia
                ) {$alias} ON {$alias}.id_referencia = r.id_referencia";
    }

    private function primeraImagenJoin(string $alias = 'img'): string {
        return "LEFT JOIN (
                    SELECT id_producto,
                           MIN(url) KEEP (DENSE_RANK FIRST ORDER BY NVL(orden, 999999), id_imagen) AS imagen
                    FROM producto_imagen
                    GROUP BY id_producto
                ) {$alias} ON {$alias}.id_producto = p.id_producto";
    }

    // 🔥 CATÁLOGO (IMPORTANTE PARA TIENDA)
    public function obtenerCatalogo(bool $includeDescripcion = true) {
        $columns = $this->productoColumns('p', false, 'c', 'img', $includeDescripcion);
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();

        $query = "SELECT $columns
                FROM producto p
                INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                $referenciaJoin
                $stockJoin
                $imageJoin
                WHERE NVL(p.estado, 'false') = 'true'
                ORDER BY c.nombre, p.nombre";

        $stmt = oci_parse($this->conn, $query);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerTodos() {
        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();
        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  ORDER BY p.id_producto DESC";
        $stmt = oci_parse($this->conn, $query);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerPorId($id) {
        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();
        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  WHERE p.id_producto = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->normalizeRow($row) : null;
    }

    public function obtenerPorReferencia($idReferencia) {
        $idReferencia = (int) $idReferencia;
        if ($idReferencia <= 0) {
            return null;
        }

        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();
        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  WHERE r.id_referencia = :id_referencia";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_referencia', $idReferencia, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->normalizeRow($row) : null;
    }

    public function obtenerImagenes($id_producto) {
        $query = "SELECT id_imagen, id_producto, url, orden
                  FROM producto_imagen
                  WHERE id_producto = :id_producto
                  ORDER BY orden";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_producto', $id_producto);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerPorIds($ids) {
        if (empty($ids)) {
            return [];
        }

        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));
        if (empty($ids)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $index => $id) {
            $param = ':id' . $index;
            $placeholders[] = $param;
            $params[$param] = $id;
        }
        $placeholdersStr = implode(',', $placeholders);

        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();
        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  WHERE p.id_producto IN ($placeholdersStr)
                  ORDER BY p.nombre";

        $stmt = oci_parse($this->conn, $query);
        $bindValues = [];
        foreach ($params as $param => $value) {
            $bindValues[$param] = $value;
            oci_bind_by_name($stmt, $param, $bindValues[$param], -1, SQLT_INT);
        }
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerMasVendidos($limite = 5) {
        $limite = max(1, min(10, (int) $limite));

        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();

        $query = "SELECT $columns,
                        ventas.total_vendido
                FROM producto p
                INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                $referenciaJoin
                $stockJoin
                $imageJoin
                INNER JOIN (
                    SELECT dv.id_producto, SUM(dv.cantidad) AS total_vendido
                    FROM detalle_venta dv
                    INNER JOIN venta v ON v.id_venta = dv.id_venta
                    INNER JOIN pago pg ON pg.id_venta = v.id_venta
                    WHERE UPPER(TRIM(pg.estado)) = 'COMPLETADO'
                    GROUP BY dv.id_producto
                ) ventas ON ventas.id_producto = p.id_producto
                ORDER BY ventas.total_vendido DESC, p.id_producto DESC
                FETCH FIRST :limite ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':limite', $limite, -1, SQLT_INT);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }

        oci_free_statement($stmt);
        return $results;
    }

    public function obtenerProductosNuevos($limite = 10) {
        $limite = max(1, min(10, (int) $limite));

        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();

        $query = "SELECT $columns
                FROM producto p
                INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                $referenciaJoin
                $stockJoin
                $imageJoin
                ORDER BY p.id_producto DESC
                FETCH FIRST :limite ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':limite', $limite, -1, SQLT_INT);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }

        oci_free_statement($stmt);
        return $results;
    }

    private function normalizeFilterList($values, bool $numeric = false): array {
        if ($values === null) {
            return [];
        }

        $values = is_array($values) ? $values : [$values];
        $clean = [];
        foreach ($values as $value) {
            $value = $numeric ? preg_replace('/\D/', '', (string) $value) : trim((string) $value);
            if ($value !== '') {
                $clean[] = $numeric ? (int) $value : $value;
            }
        }

        return array_values(array_unique($clean));
    }

    private function buildInCondition(string $column, string $prefix, array $values, array &$binds, bool $numeric = false): string {
        if (empty($values)) {
            return '';
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $param = ':' . $prefix . $index;
            $placeholders[] = $param;
            $binds[] = [
                'param' => $param,
                'value' => $value,
                'type' => $numeric ? SQLT_INT : SQLT_CHR
            ];
        }

        return " AND $column IN (" . implode(',', $placeholders) . ")";
    }

    private function bindDynamicValues($stmt, array $binds): void {
        $this->dynamicBindValues = [];
        foreach ($binds as $bind) {
            $this->dynamicBindValues[$bind['param']] = $bind['value'];
            oci_bind_by_name($stmt, $bind['param'], $this->dynamicBindValues[$bind['param']], -1, $bind['type']);
        }
    }

    private function obtenerValoresDistinct(string $query): array {
        $stmt = oci_parse($this->conn, $query);
        oci_execute($stmt);

        $values = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $row = $this->normalizeRow($row);
            $value = trim((string) ($row['valor'] ?? ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }
        oci_free_statement($stmt);

        return $values;
    }

    public function obtenerOpcionesCompatibilidadVehiculo(): array {
        $rangosQuery = "SELECT MIN(ANO_INICIO) AS ANO_MIN, MAX(ANO_FIN) AS ANO_MAX
                        FROM V_COMPATIBILIDADES_VEHICULO
                        WHERE ANO_INICIO IS NOT NULL AND ANO_FIN IS NOT NULL";
        $stmt = oci_parse($this->conn, $rangosQuery);
        oci_execute($stmt);
        $rangos = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        $anos = [];
        if ($rangos) {
            $rangos = $this->normalizeRow($rangos);
            $min = (int) ($rangos['ano_min'] ?? 0);
            $max = (int) ($rangos['ano_max'] ?? 0);
            if ($min > 0 && $max >= $min && ($max - $min) <= 120) {
                $anos = range($min, $max);
            }
        }

        return [
            'marcas' => $this->obtenerValoresDistinct("SELECT DISTINCT MARCA_VEHICULO AS VALOR FROM V_COMPATIBILIDADES_VEHICULO WHERE MARCA_VEHICULO IS NOT NULL ORDER BY MARCA_VEHICULO"),
            'modelos' => $this->obtenerValoresDistinct("SELECT DISTINCT MODELO_VEHICULO AS VALOR FROM V_COMPATIBILIDADES_VEHICULO WHERE MODELO_VEHICULO IS NOT NULL ORDER BY MODELO_VEHICULO"),
            'anos' => $anos
        ];
    }

    public function obtenerOpcionesCompatibilidadMaquinaria(): array {
        return [
            'tipos' => $this->obtenerValoresDistinct("SELECT DISTINCT TIPO_MAQUINARIA AS VALOR FROM V_COMPATIBILIDADES_MAQUINARIA WHERE TIPO_MAQUINARIA IS NOT NULL ORDER BY TIPO_MAQUINARIA"),
            'marcas' => $this->obtenerValoresDistinct("SELECT DISTINCT MARCA_MAQUINARIA AS VALOR FROM V_COMPATIBILIDADES_MAQUINARIA WHERE MARCA_MAQUINARIA IS NOT NULL ORDER BY MARCA_MAQUINARIA"),
            'modelos' => $this->obtenerValoresDistinct("SELECT DISTINCT MODELO_MAQUINARIA AS VALOR FROM V_COMPATIBILIDADES_MAQUINARIA WHERE MODELO_MAQUINARIA IS NOT NULL ORDER BY MODELO_MAQUINARIA")
        ];
    }

    public function filtrarVehiculo($marca = null, $modelo = null, $ano = null): array {
        $marcas = $this->normalizeFilterList($marca);
        $modelos = $this->normalizeFilterList($modelo);
        $anos = $this->normalizeFilterList($ano, true);
        $binds = [];

        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();
        $marcaCondition = $this->buildInCondition('MARCA_VEHICULO', 'veh_marca', $marcas, $binds);
        $modeloCondition = $this->buildInCondition('MODELO_VEHICULO', 'veh_modelo', $modelos, $binds);
        $anioCondition = '';
        if (!empty($anos)) {
            $anioParts = [];
            foreach ($anos as $index => $year) {
                $param = ':veh_anio' . $index;
                $anioParts[] = "$param BETWEEN ANO_INICIO AND ANO_FIN";
                $binds[] = [
                    'param' => $param,
                    'value' => $year,
                    'type' => SQLT_INT
                ];
            }
            $anioCondition = ' AND (' . implode(' OR ', $anioParts) . ')';
        }

        $query = "SELECT $columns
                  FROM PRODUCTO p
                  INNER JOIN CATEGORIA_PRODUCTO c ON c.ID_CATEGORIA = p.ID_CATEGORIA
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  WHERE p.ID_PRODUCTO IN (
                      SELECT DISTINCT ID_PRODUCTO
                      FROM V_COMPATIBILIDADES_VEHICULO
                      WHERE 1 = 1
                      $marcaCondition
                      $modeloCondition
                      $anioCondition
                  )
                  AND UPPER(NVL(p.ESTADO, 'ACTIVO')) = 'ACTIVO'
                  ORDER BY c.NOMBRE, p.NOMBRE, r.NUMERO_REFERENCIA";

        $stmt = oci_parse($this->conn, $query);
        $this->bindDynamicValues($stmt, $binds);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function filtrarMaquinaria($tipo = null, $marca = null, $modelo = null): array {
        $tipos = $this->normalizeFilterList($tipo);
        $marcas = $this->normalizeFilterList($marca);
        $modelos = $this->normalizeFilterList($modelo);
        $binds = [];

        $columns = $this->productoColumns('p');
        $imageJoin = $this->primeraImagenJoin();
        $referenciaJoin = $this->referenciaJoin();
        $stockJoin = $this->stockReferenciaJoin();
        $tipoCondition = $this->buildInCondition('TIPO_MAQUINARIA', 'maq_tipo', $tipos, $binds);
        $marcaCondition = $this->buildInCondition('MARCA_MAQUINARIA', 'maq_marca', $marcas, $binds);
        $modeloCondition = $this->buildInCondition('MODELO_MAQUINARIA', 'maq_modelo', $modelos, $binds);
        $query = "SELECT $columns
                  FROM PRODUCTO p
                  INNER JOIN CATEGORIA_PRODUCTO c ON c.ID_CATEGORIA = p.ID_CATEGORIA
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  WHERE p.ID_PRODUCTO IN (
                      SELECT DISTINCT ID_PRODUCTO
                      FROM V_COMPATIBILIDADES_MAQUINARIA
                      WHERE 1 = 1
                      $tipoCondition
                      $marcaCondition
                      $modeloCondition
                  )
                  AND UPPER(NVL(p.ESTADO, 'ACTIVO')) = 'ACTIVO'
                  ORDER BY c.NOMBRE, p.NOMBRE, r.NUMERO_REFERENCIA";

        $stmt = oci_parse($this->conn, $query);
        $this->bindDynamicValues($stmt, $binds);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function filtrarCompatibilidadVehiculo(string $marca, string $modelo, int $ano): array {
        return $this->filtrarVehiculo($marca, $modelo, $ano);
    }

    public function filtrarCompatibilidadMaquinaria(string $tipo, string $marca, string $modelo): array {
        return $this->filtrarMaquinaria($tipo, $marca, $modelo);
    }

    public function crear($datos) {
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true);

        $query = "INSERT INTO producto (nombre, codigo, descripcion, precio, estado, id_categoria) 
                  VALUES (:nombre, :codigo, :descripcion, :precio, :estado, :id_categoria)
                  RETURNING id_producto INTO :id";
        
        $estado = $estadoBool ? 'Activo' : 'Inactivo';

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':nombre', $datos['nombre']);
        oci_bind_by_name($stmt, ':codigo', $datos['codigo'], -1, SQLT_INT);
        oci_bind_by_name($stmt, ':descripcion', $datos['descripcion']);
        $precio = number_format((float) $datos['precio'], 2, '.', '');
        oci_bind_by_name($stmt, ':precio', $precio, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':estado', $estado);
        oci_bind_by_name($stmt, ':id_categoria', $datos['id_categoria'], -1, SQLT_INT);
        $id = null;
        oci_bind_by_name($stmt, ':id', $id, -1, SQLT_INT);
        oci_execute($stmt);

        oci_fetch($stmt); // To populate the OUT parameter
        oci_free_statement($stmt);

        return (int) $id;
    }

    public function guardarImagen($id_producto, $url, $orden) {
        $query = "INSERT INTO producto_imagen (id_producto, url, orden) 
                  VALUES (:id_producto, :url, :orden)";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_producto', $id_producto, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':url', $url);
        oci_bind_by_name($stmt, ':orden', $orden, -1, SQLT_INT);
        oci_execute($stmt);
        oci_free_statement($stmt);
    }

    public function actualizar($id, $datos) {
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true);

        $query = "UPDATE producto SET 
                  nombre = :nombre,
                  codigo = :codigo,
                  descripcion = :descripcion,
                  precio = :precio,
                  estado = :estado,
                  id_categoria = :id_categoria
                  WHERE id_producto = :id";

        $estado = $estadoBool ? 'Activo' : 'Inactivo';

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':nombre', $datos['nombre']);
        oci_bind_by_name($stmt, ':codigo', $datos['codigo'], -1, SQLT_INT);
        oci_bind_by_name($stmt, ':descripcion', $datos['descripcion']);
        $precio = number_format((float) $datos['precio'], 2, '.', '');
        oci_bind_by_name($stmt, ':precio', $precio, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':estado', $estado);
        oci_bind_by_name($stmt, ':id_categoria', $datos['id_categoria'], -1, SQLT_INT);
        oci_execute($stmt);
        oci_free_statement($stmt);
    }

    public function eliminar($id) {
        $query = "DELETE FROM producto WHERE id_producto = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id, -1, SQLT_INT);
        oci_execute($stmt);
        oci_free_statement($stmt);
    }

    public function eliminarImagenes($id_producto) {
        $query = "DELETE FROM producto_imagen WHERE id_producto = :id_producto";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_producto', $id_producto, -1, SQLT_INT);
        oci_execute($stmt);
        oci_free_statement($stmt);
    }

    public function eliminarImagen($id_imagen) {
        $query = "DELETE FROM producto_imagen WHERE id_imagen = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id_imagen, -1, SQLT_INT);
        oci_execute($stmt);
        oci_free_statement($stmt);
    }

    public function obtenerUrlImagen($id_imagen) {
        $query = "SELECT url FROM producto_imagen WHERE id_imagen = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id_imagen, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->normalizeRow($row) : null;
    }

    public function obtenerCategorias() {
        $query = "SELECT id_categoria, nombre
                  FROM categoria_producto
                  ORDER BY nombre";
        $stmt = oci_parse($this->conn, $query);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $results;
    }
}
