<?php
class ProductoModel {

    private $conn;

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

    private function productoColumns(string $alias = 'p', bool $includeTotalVendido = false, string $categoriaAlias = 'c'): string {
        $prefix = $alias !== '' ? $alias . '.' : '';
        $categoriaPrefix = $categoriaAlias !== '' ? $categoriaAlias . '.' : '';
        $columns = [
            "{$prefix}id_producto",
            "{$prefix}nombre",
            "{$prefix}codigo",
            "{$prefix}descripcion",
            "{$prefix}precio",
            "{$prefix}stock_p",
            "{$prefix}estado",
            "{$prefix}id_categoria",
            "{$categoriaPrefix}nombre AS categoria_nombre",
            "(SELECT MIN(pi.url) KEEP (DENSE_RANK FIRST ORDER BY NVL(pi.orden, 999999), pi.id_imagen)
              FROM producto_imagen pi
              WHERE pi.id_producto = {$prefix}id_producto) AS imagen"
        ];

        if ($includeTotalVendido) {
            $columns[] = "0 AS total_vendido";
        }

        return implode(",\n                         ", $columns);
    }

    // 🔥 CATÁLOGO (IMPORTANTE PARA TIENDA)
    public function obtenerCatalogo() {
    $columns = $this->productoColumns('p');
    $query = "SELECT $columns
              FROM producto p
              INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
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
        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
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
        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                  WHERE p.id_producto = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id);
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
        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
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
        $columns = $this->productoColumns('p', true);

        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
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

    public function obtenerProductosNuevos($limite = 10) {
        $limite = max(1, min(10, (int) $limite));
        $columns = $this->productoColumns('p');

        $query = "SELECT $columns
                  FROM producto p
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
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

    public function crear($datos) {
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true);

        $query = "INSERT INTO producto (nombre, codigo, descripcion, precio, stock_p, estado, id_categoria) 
                  VALUES (:nombre, :codigo, :descripcion, :precio, :stock, :estado, :id_categoria)
                  RETURNING id_producto INTO :id";
        
        $estado = $estadoBool ? 'Activo' : 'Inactivo';

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':nombre', $datos['nombre']);
        oci_bind_by_name($stmt, ':codigo', $datos['codigo'], -1, SQLT_INT);
        oci_bind_by_name($stmt, ':descripcion', $datos['descripcion']);
        $precio = number_format((float) $datos['precio'], 2, '.', '');
        oci_bind_by_name($stmt, ':precio', $precio, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':stock', $datos['stock'], -1, SQLT_INT);
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
                  stock_p = :stock,
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
        oci_bind_by_name($stmt, ':stock', $datos['stock'], -1, SQLT_INT);
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
