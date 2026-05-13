<?php
class ProductoModel {

    private $conn;
    private $dynamicBindValues = [];
    private $filterOptionCache = [];

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

    private function anexarCompatibilidades(array $productos, int $limitePorProducto = 3): array {
        $productosIds = array_values(array_unique(array_filter(array_map(
            fn($producto) => (int) ($producto['id_producto'] ?? 0),
            $productos
        ), fn($id) => $id > 0)));

        if (empty($productosIds)) {
            return $productos;
        }

        $compatibilidades = [];
        foreach ($productosIds as $idProducto) {
            $compatibilidades[$idProducto] = [
                'vehiculos' => [],
                'maquinarias' => []
            ];
        }

        $placeholders = [];
        $bindValues = [];
        foreach ($productosIds as $index => $idProducto) {
            $param = ':prod' . $index;
            $placeholders[] = $param;
            $bindValues[$param] = $idProducto;
        }
        $placeholdersSql = implode(',', $placeholders);

        $referenciaActiva = $this->activeReferenciaCondition('r');
        
        // Optimizamos: usamos ROW_NUMBER para traer solo lo necesario por producto
        $vehiculoQuery = "SELECT id_producto, id_referencia, marca_vehiculo, modelo_vehiculo, ano_inicio, ano_fin
                          FROM (
                              SELECT r.id_producto,
                                     cv.id_referencia,
                                     cv.marca_vehiculo,
                                     cv.modelo_vehiculo,
                                     cv.ano_inicio,
                                     cv.ano_fin,
                                     ROW_NUMBER() OVER (PARTITION BY r.id_producto ORDER BY cv.marca_vehiculo, cv.modelo_vehiculo) as rn
                              FROM referencia_producto r
                              INNER JOIN compatibilidad_vehiculo cv ON cv.id_referencia = r.id_referencia
                              WHERE r.id_producto IN ($placeholdersSql)
                              AND $referenciaActiva
                          ) WHERE rn <= :limit_compat";
                          
        $stmt = oci_parse($this->conn, $vehiculoQuery);
        foreach ($bindValues as $param => $value) {
            oci_bind_by_name($stmt, $param, $bindValues[$param], -1, SQLT_INT);
        }
        oci_bind_by_name($stmt, ':limit_compat', $limitePorProducto, -1, SQLT_INT);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            $item = $this->normalizeRow($row);
            $idProducto = (int) ($item['id_producto'] ?? 0);
            if (isset($compatibilidades[$idProducto])) {
                $compatibilidades[$idProducto]['vehiculos'][] = $item;
            }
        }
        oci_free_statement($stmt);

        $maquinariaQuery = "SELECT id_producto, id_referencia, tipo_maquinaria, marca_maquinaria, modelo_maquinaria
                            FROM (
                                SELECT r.id_producto,
                                       cm.id_referencia,
                                       cm.tipo_maquinaria,
                                       cm.marca_maquinaria,
                                       cm.modelo_maquinaria,
                                       ROW_NUMBER() OVER (PARTITION BY r.id_producto ORDER BY cm.tipo_maquinaria, cm.marca_maquinaria) as rn
                                FROM referencia_producto r
                                INNER JOIN compatibilidad_maquinaria cm ON cm.id_referencia = r.id_referencia
                                WHERE r.id_producto IN ($placeholdersSql)
                                AND $referenciaActiva
                            ) WHERE rn <= :limit_compat";
                            
        $stmt = oci_parse($this->conn, $maquinariaQuery);
        foreach ($bindValues as $param => $value) {
            oci_bind_by_name($stmt, $param, $bindValues[$param], -1, SQLT_INT);
        }
        oci_bind_by_name($stmt, ':limit_compat', $limitePorProducto, -1, SQLT_INT);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            $item = $this->normalizeRow($row);
            $idProducto = (int) ($item['id_producto'] ?? 0);
            if (isset($compatibilidades[$idProducto])) {
                $compatibilidades[$idProducto]['maquinarias'][] = $item;
            }
        }
        oci_free_statement($stmt);

        foreach ($productos as &$producto) {
            $idProducto = (int) ($producto['id_producto'] ?? 0);
            $producto['compatibilidades'] = $compatibilidades[$idProducto] ?? [
                'vehiculos' => [],
                'maquinarias' => []
            ];
        }
        unset($producto);

        return $productos;
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

    private function productoColumnsRapidas(bool $includeDescripcion = true): string {
        $stockExpression = "(NVL((SELECT SUM(NVL(cv.STOCK_P, 0)) FROM COMPATIBILIDAD_VEHICULO cv WHERE cv.ID_REFERENCIA = r.ID_REFERENCIA), 0)
                            + NVL((SELECT SUM(NVL(cm.STOCK_P, 0)) FROM COMPATIBILIDAD_MAQUINARIA cm WHERE cm.ID_REFERENCIA = r.ID_REFERENCIA), 0))";

        $columns = [
            "p.ID_PRODUCTO",
            "r.ID_REFERENCIA",
            "r.NUMERO_REFERENCIA",
            "r.MARCA",
            "r.FABRICANTE",
            "p.NOMBRE",
            "p.CODIGO",
            "p.PRECIO",
            "{$stockExpression} AS STOCK_P",
            "p.ESTADO",
            "p.ID_CATEGORIA",
            "c.NOMBRE AS CATEGORIA_NOMBRE",
            "(SELECT MIN(pi.URL) KEEP (DENSE_RANK FIRST ORDER BY NVL(pi.ORDEN, 999999), pi.ID_IMAGEN)
              FROM PRODUCTO_IMAGEN pi
              WHERE pi.ID_PRODUCTO = p.ID_PRODUCTO) AS IMAGEN"
        ];

        if ($includeDescripcion) {
            array_splice($columns, 3, 0, "p.DESCRIPCION");
        }

        return implode(",\n                         ", $columns);
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
                ORDER BY c.nombre, CASE WHEN NVL(stk.stock_p, 0) <= 0 THEN 1 ELSE 0 END, p.nombre";

        $stmt = oci_parse($this->conn, $query);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $this->anexarCompatibilidades($results);
    }

    public function obtenerTodos(bool $conCompatibilidades = false) {
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

        return $conCompatibilidades ? $this->anexarCompatibilidades($results) : $results;
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

        if (!$row) {
            return null;
        }

        $productos = $this->anexarCompatibilidades([$this->normalizeRow($row)]);
        return $productos[0] ?? null;
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

        if (!$row) {
            return null;
        }

        $productos = $this->anexarCompatibilidades([$this->normalizeRow($row)]);
        return $productos[0] ?? null;
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
                  ORDER BY CASE WHEN NVL(stk.stock_p, 0) <= 0 THEN 1 ELSE 0 END, p.nombre";

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

    public function obtenerRealtimePorReferencias(array $idsReferencia): array {
        $idsReferencia = array_values(array_unique(array_filter(
            array_map('intval', $idsReferencia),
            fn($id) => $id > 0
        )));

        if (empty($idsReferencia)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($idsReferencia as $index => $idReferencia) {
            $param = ':ref' . $index;
            $placeholders[] = $param;
            $params[$param] = $idReferencia;
        }

        $query = "SELECT r.ID_REFERENCIA,
                         p.ID_PRODUCTO,
                         p.PRECIO,
                         p.ESTADO,
                         NVL(stk.STOCK_P, 0) AS STOCK_P
                  FROM REFERENCIA_PRODUCTO r
                  INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = r.ID_PRODUCTO
                  LEFT JOIN (
                      SELECT ID_REFERENCIA, SUM(STOCK_P) AS STOCK_P
                      FROM (
                          SELECT ID_REFERENCIA, NVL(STOCK_P, 0) AS STOCK_P
                          FROM COMPATIBILIDAD_VEHICULO
                          UNION ALL
                          SELECT ID_REFERENCIA, NVL(STOCK_P, 0) AS STOCK_P
                          FROM COMPATIBILIDAD_MAQUINARIA
                      )
                      GROUP BY ID_REFERENCIA
                  ) stk ON stk.ID_REFERENCIA = r.ID_REFERENCIA
                  WHERE r.ID_REFERENCIA IN (" . implode(',', $placeholders) . ")";

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
                    WHERE UPPER(TRIM(pg.estado)) IN ('APPROVED', 'PAGADO', 'COMPLETADO')
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
        return $this->anexarCompatibilidades($results);
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
        return $this->anexarCompatibilidades($results);
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

    private function activeProductoCondition(string $alias = 'p'): string {
        return "UPPER(TRIM(NVL({$alias}.ESTADO, ''))) IN ('ACTIVO', 'TRUE')";
    }

    private function activeReferenciaCondition(string $alias = 'r'): string {
        return "UPPER(TRIM(NVL({$alias}.ESTADO, ''))) IN ('ACTIVO', 'TRUE')";
    }

    private function buildUpperInCondition(string $column, string $prefix, array $values, array &$binds): string {
        if (empty($values)) {
            return '';
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $param = ':' . $prefix . $index;
            $placeholders[] = "UPPER(TRIM($param))";
            $binds[] = [
                'param' => $param,
                'value' => (string) $value,
                'type' => SQLT_CHR
            ];
        }

        return " AND UPPER(TRIM($column)) IN (" . implode(',', $placeholders) . ")";
    }

    private function bindArray($stmt, array $values, string $prefix): void {
        foreach (array_values($values) as $index => $value) {
            $param = ':' . $prefix . $index;
            $this->dynamicBindValues[$param] = (string) $value;
            oci_bind_by_name($stmt, $param, $this->dynamicBindValues[$param], -1, SQLT_CHR);
        }
    }

    private function buildMaquinariaFilterConditions(array $marcas, array $modelos, array $tipos, array &$binds): string {
        return $this->buildUpperInCondition('cm.MARCA_MAQUINARIA', 'maq_marca', $marcas, $binds)
            . $this->buildUpperInCondition('cm.MODELO_MAQUINARIA', 'maq_modelo', $modelos, $binds)
            . $this->buildUpperInCondition('cm.TIPO_MAQUINARIA', 'maq_tipo', $tipos, $binds);
    }

    private function obtenerValoresMaquinaria(string $column, string $alias, array $marcas = [], array $modelos = [], array $tipos = []): array {
        $cacheKey = $column . '|' . implode('~', $marcas) . '|' . implode('~', $modelos) . '|' . implode('~', $tipos);
        if (array_key_exists($cacheKey, $this->filterOptionCache)) {
            return $this->filterOptionCache[$cacheKey];
        }

        $binds = [];
        $conditions = $this->buildMaquinariaFilterConditions($marcas, $modelos, $tipos, $binds);
        $productoActivo = $this->activeProductoCondition('p');
        $referenciaActiva = $this->activeReferenciaCondition('r');
        $query = "SELECT DISTINCT $column AS VALOR
                  FROM PRODUCTO p
                  INNER JOIN REFERENCIA_PRODUCTO r ON r.ID_PRODUCTO = p.ID_PRODUCTO
                  INNER JOIN COMPATIBILIDAD_MAQUINARIA cm ON cm.ID_REFERENCIA = r.ID_REFERENCIA
                  WHERE $productoActivo
                  AND $referenciaActiva
                  AND $column IS NOT NULL
                  $conditions
                  ORDER BY $alias";

        $stmt = oci_parse($this->conn, $query);
        $this->bindDynamicValues($stmt, $binds);
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

        if (!empty($values) || (empty($marcas) && empty($modelos) && empty($tipos))) {
            $this->filterOptionCache[$cacheKey] = $values;
            return $values;
        }

        $values = $this->obtenerValoresMaquinaria($column, $alias);
        $this->filterOptionCache[$cacheKey] = $values;
        return $values;
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
            'tipos' => $this->obtenerTipos([], []),
            'marcas' => $this->obtenerMarcas([], []),
            'modelos' => $this->obtenerModelos([], [])
        ];
    }

    public function obtenerMarcas($modelos = [], $tipos = []): array {
        $modelos = $this->normalizeFilterList($modelos);
        $tipos = $this->normalizeFilterList($tipos);

        return $this->obtenerValoresMaquinaria('cm.MARCA_MAQUINARIA', 'VALOR', [], $modelos, $tipos);
    }

    public function obtenerModelos($marcas = [], $tipos = []): array {
        $marcas = $this->normalizeFilterList($marcas);
        $tipos = $this->normalizeFilterList($tipos);

        return $this->obtenerValoresMaquinaria('cm.MODELO_MAQUINARIA', 'VALOR', $marcas, [], $tipos);
    }

    public function obtenerTipos($marcas = [], $modelos = []): array {
        $marcas = $this->normalizeFilterList($marcas);
        $modelos = $this->normalizeFilterList($modelos);

        return $this->obtenerValoresMaquinaria('cm.TIPO_MAQUINARIA', 'VALOR', $marcas, $modelos, []);
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
                  ORDER BY c.NOMBRE, CASE WHEN NVL(stk.stock_p, 0) <= 0 THEN 1 ELSE 0 END, p.NOMBRE, r.NUMERO_REFERENCIA";

        $stmt = oci_parse($this->conn, $query);
        $this->bindDynamicValues($stmt, $binds);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $this->anexarCompatibilidades($results);
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
        $maquinariaConditions = $this->buildMaquinariaFilterConditions($marcas, $modelos, $tipos, $binds);
        $productoActivo = $this->activeProductoCondition('p');
        $productoFiltroActivo = $this->activeProductoCondition('p2');
        $referenciaActiva = $this->activeReferenciaCondition('r');
        $query = "SELECT $columns
                  FROM PRODUCTO p
                  INNER JOIN CATEGORIA_PRODUCTO c ON c.ID_CATEGORIA = p.ID_CATEGORIA
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  WHERE p.ID_PRODUCTO IN (
                      SELECT DISTINCT p2.ID_PRODUCTO
                      FROM PRODUCTO p2
                      INNER JOIN REFERENCIA_PRODUCTO r ON r.ID_PRODUCTO = p2.ID_PRODUCTO
                      INNER JOIN COMPATIBILIDAD_MAQUINARIA cm ON cm.ID_REFERENCIA = r.ID_REFERENCIA
                      WHERE 1 = 1
                      AND $productoFiltroActivo
                      AND $referenciaActiva
                      $maquinariaConditions
                  )
                  AND $productoActivo
                  ORDER BY c.NOMBRE, CASE WHEN NVL(stk.stock_p, 0) <= 0 THEN 1 ELSE 0 END, p.NOMBRE, r.NUMERO_REFERENCIA";

        $stmt = oci_parse($this->conn, $query);
        $this->bindDynamicValues($stmt, $binds);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $this->anexarCompatibilidades($results);
    }

    public function filtrarProductos($marcas = [], $modelos = [], $tipos = []): array {
        return $this->filtrarMaquinaria($tipos, $marcas, $modelos);
    }

    public function filtrarCompatibilidadVehiculo(string $marca, string $modelo, int $ano): array {
        return $this->filtrarVehiculo($marca, $modelo, $ano);
    }

    public function filtrarCompatibilidadMaquinaria(string $tipo, string $marca, string $modelo): array {
        return $this->filtrarMaquinaria($tipo, $marca, $modelo);
    }

    public function crear($datos, bool $autoCommit = true) {
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true);

        $query = "INSERT INTO producto (nombre, codigo, descripcion, precio, estado, id_categoria)
                  VALUES (:nombre, :codigo, :descripcion, :precio, :estado, :id_categoria)
                  RETURNING id_producto INTO :id";

        $estado = $estadoBool ? 'Activo' : 'Inactivo';
        $nombre = $datos['nombre'];
        $codigo = $datos['codigo'];
        $descripcion = $datos['descripcion'] ?? '';
        $precio = number_format((float) $datos['precio'], 2, '.', '');
        $idCategoria = (int) $datos['id_categoria'];

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':nombre', $nombre);
        oci_bind_by_name($stmt, ':codigo', $codigo, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':descripcion', $descripcion);
        oci_bind_by_name($stmt, ':precio', $precio, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':estado', $estado);
        oci_bind_by_name($stmt, ':id_categoria', $idCategoria, -1, SQLT_INT);
        $id = null;
        oci_bind_by_name($stmt, ':id', $id, -1, SQLT_INT);
        oci_execute($stmt, $autoCommit ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmt);

        return (int) $id;
    }

    private function nextval(string $sequence): int {
        $stmt = oci_parse($this->conn, "SELECT {$sequence}.NEXTVAL FROM DUAL");
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        $row = oci_fetch_array($stmt, OCI_NUM + OCI_RETURN_NULLS);
        oci_free_statement($stmt);
        return (int) ($row[0] ?? 0);
    }

    public function crearReferencia(int $idProducto, array $datos): int {
        $idRef      = $this->nextval('SEQ_REFERENCIA_PRODUCTO');
        $numeroRef  = $datos['numero_referencia'] ?? '';
        $marca      = $datos['marca']             ?? '';
        $fabricante = $datos['fabricante']        ?? '';
        $especif    = $datos['especificaciones']  ?? '';
        $estado     = 'Activo';

        $stmt = oci_parse($this->conn,
            "INSERT INTO referencia_producto
               (id_referencia, id_producto, numero_referencia, marca, fabricante, especificaciones, estado)
             VALUES
               (:id_referencia, :id_producto, :numero_referencia, :marca, :fabricante, :especificaciones, :estado)");

        oci_bind_by_name($stmt, ':id_referencia',     $idRef,      -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_producto',       $idProducto, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':numero_referencia', $numeroRef,  -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':marca',             $marca,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':fabricante',        $fabricante, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':especificaciones',  $especif,    -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':estado',            $estado,     -1, SQLT_CHR);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $err = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception('Error al crear referencia (id=' . $idRef . '): ' . ($err['message'] ?? 'desconocido'));
        }
        oci_free_statement($stmt);
        return $idRef;
    }

    public function crearCompatibilidadVehiculo(int $idReferencia, array $datos): void {
        $idCompat = $this->nextval('SEQ_COMPATIBILIDAD_VEHICULO');
        $query = "INSERT INTO compatibilidad_vehiculo
                    (id_compatibilidad, id_referencia, marca_vehiculo, modelo_vehiculo, ano_inicio, ano_fin, motor, transmision, notas, stock_p)
                  VALUES
                    (:id_compatibilidad, :id_referencia, :marca_vehiculo, :modelo_vehiculo, :ano_inicio, :ano_fin, :motor, :transmision, :notas, :stock_p)";

        $marcaV   = $datos['marca_vehiculo']   ?? '';
        $modeloV  = $datos['modelo_vehiculo']  ?? '';
        $anoIni   = isset($datos['ano_inicio']) && $datos['ano_inicio'] !== '' ? (int)$datos['ano_inicio'] : null;
        $anoFin   = isset($datos['ano_fin'])    && $datos['ano_fin']    !== '' ? (int)$datos['ano_fin']    : null;
        $motor    = $datos['motor']             ?? '';
        $transm   = $datos['transmision']       ?? '';
        $notas    = $datos['notas']             ?? '';
        $stock    = (int) ($datos['stock_p']    ?? 0);

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_compatibilidad', $idCompat,    -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_referencia',     $idReferencia,-1, SQLT_INT);
        oci_bind_by_name($stmt, ':marca_vehiculo',    $marcaV,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':modelo_vehiculo',   $modeloV,     -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':ano_inicio',        $anoIni,      -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ano_fin',           $anoFin,      -1, SQLT_INT);
        oci_bind_by_name($stmt, ':motor',             $motor,       -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':transmision',       $transm,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':notas',             $notas,       -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':stock_p',           $stock,       -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $err = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception('Error compat vehiculo: ' . ($err['message'] ?? 'desconocido'));
        }
        oci_free_statement($stmt);
    }

    public function crearCompatibilidadMaquinaria(int $idReferencia, array $datos): void {
        $idCompat = $this->nextval('SEQ_COMPATIBILIDAD_MAQUINARIA');
        // PK real de la tabla es ID_COMPATIBILIDAD_MAQ
        $query = "INSERT INTO compatibilidad_maquinaria
                    (id_compatibilidad_maq, id_referencia, tipo_maquinaria, marca_maquinaria, modelo_maquinaria, componente, ano_inicio, ano_fin, notas, stock_p)
                  VALUES
                    (:id_compatibilidad_maq, :id_referencia, :tipo_maquinaria, :marca_maquinaria, :modelo_maquinaria, :componente, :ano_inicio, :ano_fin, :notas, :stock_p)";

        $tipo     = $datos['tipo_maquinaria']   ?? '';
        $marcaM   = $datos['marca_maquinaria']  ?? '';
        $modeloM  = $datos['modelo_maquinaria'] ?? '';
        $componen = $datos['componente']         ?? '';
        $anoIni   = isset($datos['ano_inicio']) && $datos['ano_inicio'] !== '' ? (int)$datos['ano_inicio'] : null;
        $anoFin   = isset($datos['ano_fin'])    && $datos['ano_fin']    !== '' ? (int)$datos['ano_fin']    : null;
        $notas    = $datos['notas']              ?? '';
        $stock    = (int) ($datos['stock_p']     ?? 0);

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_compatibilidad_maq', $idCompat,    -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_referencia',         $idReferencia,-1, SQLT_INT);
        oci_bind_by_name($stmt, ':tipo_maquinaria',       $tipo,        -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':marca_maquinaria',      $marcaM,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':modelo_maquinaria',     $modeloM,     -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':componente',            $componen,    -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':ano_inicio',            $anoIni,      -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ano_fin',               $anoFin,      -1, SQLT_INT);
        oci_bind_by_name($stmt, ':notas',                 $notas,       -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':stock_p',               $stock,       -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $err = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception('Error compat maquinaria: ' . ($err['message'] ?? 'desconocido'));
        }
        oci_free_statement($stmt);
    }

    public function obtenerTodasCompatibilidades(int $idProducto): array {
        $result = ['vehiculos' => [], 'maquinarias' => []];

        $stmt = oci_parse($this->conn,
            "SELECT cv.id_referencia, cv.marca_vehiculo, cv.modelo_vehiculo,
                    cv.ano_inicio, cv.ano_fin, cv.motor, cv.transmision, cv.notas, cv.stock_p
             FROM referencia_producto rp
             JOIN compatibilidad_vehiculo cv ON cv.id_referencia = rp.id_referencia
             WHERE rp.id_producto = :id ORDER BY cv.marca_vehiculo, cv.modelo_vehiculo");
        oci_bind_by_name($stmt, ':id', $idProducto, -1, SQLT_INT);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            $result['vehiculos'][] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        $stmt = oci_parse($this->conn,
            "SELECT cm.id_referencia, cm.tipo_maquinaria, cm.marca_maquinaria, cm.modelo_maquinaria,
                    cm.componente, cm.ano_inicio, cm.ano_fin, cm.notas, cm.stock_p
             FROM referencia_producto rp
             JOIN compatibilidad_maquinaria cm ON cm.id_referencia = rp.id_referencia
             WHERE rp.id_producto = :id ORDER BY cm.tipo_maquinaria, cm.marca_maquinaria");
        oci_bind_by_name($stmt, ':id', $idProducto, -1, SQLT_INT);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            $result['maquinarias'][] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        return $result;
    }

    public function obtenerReferencia(int $idProducto): array {
        $stmt = oci_parse($this->conn,
            "SELECT id_referencia, numero_referencia, marca, fabricante, especificaciones, estado
             FROM referencia_producto WHERE id_producto = :id
             ORDER BY id_referencia FETCH FIRST 1 ROWS ONLY");
        oci_bind_by_name($stmt, ':id', $idProducto, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $row ? $this->normalizeRow($row) : [];
    }

    public function actualizarReferencia(int $idProducto, array $datos): void {
        $numeroRef  = $datos['numero_referencia'] ?? '';
        $marca      = $datos['marca']             ?? '';
        $fabricante = $datos['fabricante']        ?? '';
        $especif    = $datos['especificaciones']  ?? '';

        $stmt = oci_parse($this->conn,
            "UPDATE referencia_producto
             SET numero_referencia = :numero_referencia,
                 marca             = :marca,
                 fabricante        = :fabricante,
                 especificaciones  = :especificaciones
             WHERE id_producto = :id_producto");
        oci_bind_by_name($stmt, ':numero_referencia', $numeroRef,   -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':marca',             $marca,       -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':fabricante',        $fabricante,  -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':especificaciones',  $especif,     -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':id_producto',       $idProducto,  -1, SQLT_INT);
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmt);
    }

    public function eliminarCompatibilidadesPorReferencia(int $idReferencia): void {
        foreach (['compatibilidad_vehiculo', 'compatibilidad_maquinaria'] as $tabla) {
            $stmt = oci_parse($this->conn, "DELETE FROM $tabla WHERE id_referencia = :id");
            oci_bind_by_name($stmt, ':id', $idReferencia, -1, SQLT_INT);
            oci_execute($stmt, OCI_NO_AUTO_COMMIT);
            oci_free_statement($stmt);
        }
    }

    public function commit(): void {
        oci_commit($this->conn);
    }

    public function rollback(): void {
        oci_rollback($this->conn);
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
        $estadoVal  = $datos['estado'] ?? '1';
        $estadoBool = in_array($estadoVal, ['Activo', '1', 'true', true], true);
        $estado     = $estadoBool ? 'Activo' : 'Inactivo';

        $nombre      = $datos['nombre']      ?? '';
        $codigo      = $datos['codigo']      ?? '';
        $descripcion = $datos['descripcion'] ?? '';
        $precio      = number_format((float)($datos['precio'] ?? 0), 2, '.', '');
        $idCategoria = (int)($datos['id_categoria'] ?? 0);
        $idProducto  = (int) $id;

        $stmt = oci_parse($this->conn,
            "UPDATE producto SET
               nombre       = :nombre,
               codigo       = :codigo,
               descripcion  = :descripcion,
               precio       = :precio,
               estado       = :estado,
               id_categoria = :id_categoria
             WHERE id_producto = :id");

        oci_bind_by_name($stmt, ':id',           $idProducto,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':nombre',       $nombre,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':codigo',       $codigo,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':descripcion',  $descripcion, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':precio',       $precio,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':estado',       $estado,      -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':id_categoria', $idCategoria, -1, SQLT_INT);
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmt);
    }

    public function eliminar($id) {
        $query = "DELETE FROM producto WHERE id_producto = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id, -1, SQLT_INT);
        if (!@oci_execute($stmt)) {
            $err = oci_error($stmt);
            oci_free_statement($stmt);
            $msg = $err['message'] ?? '';
            if (str_contains($msg, 'ORA-02292')) {
                throw new Exception('El producto tiene ventas o pedidos asociados y no puede eliminarse. Puedes desactivarlo desde Editar.');
            }
            throw new Exception('Error al eliminar el producto: ' . $msg);
        }
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

    public function contarProductosAvanzado(array $filters): int {
        $binds = [];
        $where = " WHERE " . $this->activeProductoCondition('p');

        if (!empty($filters['query'])) {
            $search = '%' . strtoupper(trim((string)$filters['query'])) . '%';
            $where .= " AND (UPPER(p.NOMBRE) LIKE :search_query OR UPPER(p.CODIGO) LIKE :search_query OR UPPER(p.DESCRIPCION) LIKE :search_query)";
            $binds[] = ['param' => ':search_query', 'value' => $search, 'type' => SQLT_CHR];
        }

        if (!empty($filters['categoria'])) {
            $where .= " AND UPPER(TRIM(c.NOMBRE)) = UPPER(TRIM(:cat_name))";
            $binds[] = ['param' => ':cat_name', 'value' => (string)$filters['categoria'], 'type' => SQLT_CHR];
        }

        if (!empty($filters['precio_min'])) {
            $where .= " AND p.PRECIO >= :p_min";
            $binds[] = ['param' => ':p_min', 'value' => (float)$filters['precio_min'], 'type' => SQLT_CHR];
        }
        if (!empty($filters['precio_max'])) {
            $where .= " AND p.PRECIO <= :p_max";
            $binds[] = ['param' => ':p_max', 'value' => (float)$filters['precio_max'], 'type' => SQLT_CHR];
        }

        $tipoCompat = $filters['compatibilidad_tipo'] ?? '';
        if ($tipoCompat === 'vehiculo') {
            $subWhere = " WHERE 1=1";
            $marcas = $this->normalizeFilterList($filters['vehiculo_marca'] ?? []);
            $modelos = $this->normalizeFilterList($filters['vehiculo_modelo'] ?? []);
            $anos = $this->normalizeFilterList($filters['vehiculo_ano'] ?? [], true);
            $subWhere .= $this->buildInCondition('MARCA_VEHICULO', 'v_m', $marcas, $binds);
            $subWhere .= $this->buildInCondition('MODELO_VEHICULO', 'v_mod', $modelos, $binds);
            if (!empty($anos)) {
                $anioParts = [];
                foreach ($anos as $idx => $year) {
                    $p = ':v_a' . $idx;
                    $anioParts[] = "$p BETWEEN ANO_INICIO AND ANO_FIN";
                    $binds[] = ['param' => $p, 'value' => $year, 'type' => SQLT_INT];
                }
                $subWhere .= ' AND (' . implode(' OR ', $anioParts) . ')';
            }
            $where .= " AND p.ID_PRODUCTO IN (SELECT DISTINCT ID_PRODUCTO FROM V_COMPATIBILIDADES_VEHICULO $subWhere)";
        } elseif ($tipoCompat === 'maquinaria') {
            $subWhere = " WHERE 1=1";
            $tipos = $this->normalizeFilterList($filters['maquinaria_tipo'] ?? []);
            $marcas = $this->normalizeFilterList($filters['maquinaria_marca'] ?? []);
            $modelos = $this->normalizeFilterList($filters['maquinaria_modelo'] ?? []);
            $subWhere .= $this->buildUpperInCondition('TIPO_MAQUINARIA', 'm_t', $tipos, $binds);
            $subWhere .= $this->buildUpperInCondition('MARCA_MAQUINARIA', 'm_m', $marcas, $binds);
            $subWhere .= $this->buildUpperInCondition('MODELO_MAQUINARIA', 'm_mod', $modelos, $binds);
            $where .= " AND p.ID_PRODUCTO IN (SELECT DISTINCT rp.ID_PRODUCTO FROM REFERENCIA_PRODUCTO rp INNER JOIN COMPATIBILIDAD_MAQUINARIA cm ON cm.ID_REFERENCIA = rp.ID_REFERENCIA $subWhere)";
        }

        $query = "SELECT COUNT(*) FROM PRODUCTO p INNER JOIN CATEGORIA_PRODUCTO c ON c.ID_CATEGORIA = p.ID_CATEGORIA $where";
        $stmt = oci_parse($this->conn, $query);
        $this->bindDynamicValues($stmt, $binds);
        oci_execute($stmt);
        $row = oci_fetch_array($stmt, OCI_NUM);
        oci_free_statement($stmt);
        return (int) ($row[0] ?? 0);
    }

    public function buscarProductosAvanzado(array $filters, int $limit = 0, int $offset = 0, bool $incluirCompatibilidades = true): array {
        $binds = [];
        $referenciaJoin = $this->referenciaJoin();
        $columns = $limit > 0 ? $this->productoColumnsRapidas(true) : $this->productoColumns('p');
        $imageJoin = $limit > 0 ? '' : $this->primeraImagenJoin();
        $stockJoin = $limit > 0 ? '' : $this->stockReferenciaJoin();
        $stockSortExpression = $limit > 0
            ? "(NVL((SELECT SUM(NVL(cv_sort.STOCK_P, 0)) FROM COMPATIBILIDAD_VEHICULO cv_sort WHERE cv_sort.ID_REFERENCIA = r.ID_REFERENCIA), 0)
                + NVL((SELECT SUM(NVL(cm_sort.STOCK_P, 0)) FROM COMPATIBILIDAD_MAQUINARIA cm_sort WHERE cm_sort.ID_REFERENCIA = r.ID_REFERENCIA), 0))"
            : "NVL(stk.stock_p, 0)";

        $where = " WHERE " . $this->activeProductoCondition('p');

        if (!empty($filters['query'])) {
            $search = '%' . strtoupper(trim((string)$filters['query'])) . '%';
            $where .= " AND (UPPER(p.NOMBRE) LIKE :search_query OR UPPER(p.CODIGO) LIKE :search_query OR UPPER(p.DESCRIPCION) LIKE :search_query)";
            $binds[] = ['param' => ':search_query', 'value' => $search, 'type' => SQLT_CHR];
        }

        if (!empty($filters['categoria'])) {
            $where .= " AND UPPER(TRIM(c.NOMBRE)) = UPPER(TRIM(:cat_name))";
            $binds[] = ['param' => ':cat_name', 'value' => (string)$filters['categoria'], 'type' => SQLT_CHR];
        }

        if (!empty($filters['precio_min'])) {
            $where .= " AND p.PRECIO >= :p_min";
            $binds[] = ['param' => ':p_min', 'value' => (float)$filters['precio_min'], 'type' => SQLT_CHR];
        }
        if (!empty($filters['precio_max'])) {
            $where .= " AND p.PRECIO <= :p_max";
            $binds[] = ['param' => ':p_max', 'value' => (float)$filters['precio_max'], 'type' => SQLT_CHR];
        }

        $tipoCompat = $filters['compatibilidad_tipo'] ?? '';
        if ($tipoCompat === 'vehiculo') {
            $subWhere = " WHERE 1=1";
            $marcas = $this->normalizeFilterList($filters['vehiculo_marca'] ?? []);
            $modelos = $this->normalizeFilterList($filters['vehiculo_modelo'] ?? []);
            $anos = $this->normalizeFilterList($filters['vehiculo_ano'] ?? [], true);
            $subWhere .= $this->buildInCondition('MARCA_VEHICULO', 'v_m', $marcas, $binds);
            $subWhere .= $this->buildInCondition('MODELO_VEHICULO', 'v_mod', $modelos, $binds);
            if (!empty($anos)) {
                $anioParts = [];
                foreach ($anos as $idx => $year) {
                    $p = ':v_a' . $idx;
                    $anioParts[] = "$p BETWEEN ANO_INICIO AND ANO_FIN";
                    $binds[] = ['param' => $p, 'value' => $year, 'type' => SQLT_INT];
                }
                $subWhere .= ' AND (' . implode(' OR ', $anioParts) . ')';
            }
            $where .= " AND p.ID_PRODUCTO IN (SELECT DISTINCT ID_PRODUCTO FROM V_COMPATIBILIDADES_VEHICULO $subWhere)";
        } elseif ($tipoCompat === 'maquinaria') {
            $subWhere = " WHERE 1=1";
            $tipos = $this->normalizeFilterList($filters['maquinaria_tipo'] ?? []);
            $marcas = $this->normalizeFilterList($filters['maquinaria_marca'] ?? []);
            $modelos = $this->normalizeFilterList($filters['maquinaria_modelo'] ?? []);
            $subWhere .= $this->buildUpperInCondition('TIPO_MAQUINARIA', 'm_t', $tipos, $binds);
            $subWhere .= $this->buildUpperInCondition('MARCA_MAQUINARIA', 'm_m', $marcas, $binds);
            $subWhere .= $this->buildUpperInCondition('MODELO_MAQUINARIA', 'm_mod', $modelos, $binds);
            $where .= " AND p.ID_PRODUCTO IN (SELECT DISTINCT rp.ID_PRODUCTO FROM REFERENCIA_PRODUCTO rp INNER JOIN COMPATIBILIDAD_MAQUINARIA cm ON cm.ID_REFERENCIA = rp.ID_REFERENCIA $subWhere)";
        }

        $query = "SELECT $columns
                  FROM PRODUCTO p
                  INNER JOIN CATEGORIA_PRODUCTO c ON c.ID_CATEGORIA = p.ID_CATEGORIA
                  $referenciaJoin
                  $stockJoin
                  $imageJoin
                  $where
                  ORDER BY c.NOMBRE, CASE WHEN $stockSortExpression <= 0 THEN 1 ELSE 0 END, p.NOMBRE";

        if ($limit > 0) {
            $query .= " OFFSET :offset_val ROWS FETCH NEXT :limit_val ROWS ONLY";
            $binds[] = ['param' => ':offset_val', 'value' => $offset, 'type' => SQLT_INT];
            $binds[] = ['param' => ':limit_val', 'value' => $limit, 'type' => SQLT_INT];
        }

        $stmt = oci_parse($this->conn, $query);
        $this->bindDynamicValues($stmt, $binds);
        
        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception("Error en busqueda avanzada: " . ($error['message'] ?? 'Oracle Error'));
        }

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);

        // Si es catálogo completo (muchos productos), limitamos compatibilidades a 3 por producto para ahorrar memoria/red
        if (!$incluirCompatibilidades) {
            return $results;
        }

        $limiteCompat = ($limit > 0) ? 2 : 10;
        return $this->anexarCompatibilidades($results, $limiteCompat);
    }
}
