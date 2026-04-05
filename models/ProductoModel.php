<?php
class ProductoModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Obtener todos los productos con su categoría
    public function obtenerTodos() {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM producto p
                  INNER JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                  ORDER BY p.id_producto DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener producto por ID
    public function obtenerPorId($id) {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM producto p
                  INNER JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                  WHERE p.id_producto = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener imágenes de un producto
    public function obtenerImagenes($id_producto) {
        $query = "SELECT * FROM producto_imagen WHERE id_producto = :id_producto ORDER BY orden";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_producto' => $id_producto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear producto
    public function crear($datos) {
        // Convertir estado a booleano
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true) ? true : false;
        
        $query = "INSERT INTO producto (nombre, codigo, descripcion, precio, stock_p, estado, id_categoria) 
                  VALUES (:nombre, :codigo, :descripcion, :precio, :stock, :estado, :id_categoria)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':codigo' => (int)$datos['codigo'],  // Convertir a entero
            ':descripcion' => $datos['descripcion'],
            ':precio' => (float)$datos['precio'],  // Convertir a decimal
            ':stock' => (int)$datos['stock'],  // Convertir a entero
            ':estado' => $estadoBool,
            ':id_categoria' => (int)$datos['id_categoria']
        ]);
        return $this->conn->lastInsertId();
    }

    // Guardar imagen
    public function guardarImagen($id_producto, $url, $orden) {
        $query = "INSERT INTO producto_imagen (id_producto, url, orden) 
                  VALUES (:id_producto, :url, :orden)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id_producto' => $id_producto,
            ':url' => $url,
            ':orden' => $orden
        ]);
    }

    // Actualizar producto
    public function actualizar($id, $datos) {
        // Convertir estado a booleano
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true) ? true : false;
        
        $query = "UPDATE producto SET 
                  nombre = :nombre,
                  codigo = :codigo,
                  descripcion = :descripcion,
                  precio = :precio,
                  stock_p = :stock,
                  estado = :estado,
                  id_categoria = :id_categoria
                  WHERE id_producto = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':codigo' => (int)$datos['codigo'],
            ':descripcion' => $datos['descripcion'],
            ':precio' => (float)$datos['precio'],
            ':stock' => (int)$datos['stock'],
            ':estado' => $estadoBool,
            ':id_categoria' => (int)$datos['id_categoria']
        ]);
    }

    // Eliminar producto
    public function eliminar($id) {
        $query = "DELETE FROM producto WHERE id_producto = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
    }

    // Eliminar TODAS las imágenes de un producto
    public function eliminarImagenes($id_producto) {
        $query = "DELETE FROM producto_imagen WHERE id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_producto' => $id_producto]);
    }

    // Eliminar UNA sola imagen por su ID
    public function eliminarImagen($id_imagen) {
        $query = "DELETE FROM producto_imagen WHERE id_imagen = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id_imagen]);
    }

    // Obtener URL de imagen por ID
    public function obtenerUrlImagen($id_imagen) {
        $query = "SELECT url FROM producto_imagen WHERE id_imagen = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id_imagen]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener todas las categorías
    public function obtenerCategorias() {
        $query = "SELECT * FROM categoria_producto ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    <?php
class ProductoModel {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Obtener todos los productos con su categoría
    public function obtenerTodos() {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM producto p
                  INNER JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                  ORDER BY p.id_producto DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener producto por ID
    public function obtenerPorId($id) {
        $query = "SELECT p.*, c.nombre as categoria_nombre 
                  FROM producto p
                  INNER JOIN categoria_producto c ON p.id_categoria = c.id_categoria
                  WHERE p.id_producto = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener imágenes de un producto
    public function obtenerImagenes($id_producto) {
        $query = "SELECT * FROM producto_imagen WHERE id_producto = :id_producto ORDER BY orden";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_producto' => $id_producto]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Crear producto
    public function crear($datos) {
        // Convertir estado a booleano
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true) ? true : false;
        
        $query = "INSERT INTO producto (nombre, codigo, descripcion, precio, stock_p, estado, id_categoria) 
                  VALUES (:nombre, :codigo, :descripcion, :precio, :stock, :estado, :id_categoria)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':nombre' => $datos['nombre'],
            ':codigo' => (int)$datos['codigo'],  // Convertir a entero
            ':descripcion' => $datos['descripcion'],
            ':precio' => (float)$datos['precio'],  // Convertir a decimal
            ':stock' => (int)$datos['stock'],  // Convertir a entero
            ':estado' => $estadoBool,
            ':id_categoria' => (int)$datos['id_categoria']
        ]);
        return $this->conn->lastInsertId();
    }

    // Guardar imagen
    public function guardarImagen($id_producto, $url, $orden) {
        $query = "INSERT INTO producto_imagen (id_producto, url, orden) 
                  VALUES (:id_producto, :url, :orden)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id_producto' => $id_producto,
            ':url' => $url,
            ':orden' => $orden
        ]);
    }

    // Actualizar producto
    public function actualizar($id, $datos) {
        // Convertir estado a booleano
        $estadoBool = ($datos['estado'] === 'Activo' || $datos['estado'] === '1' || $datos['estado'] === true) ? true : false;
        
        $query = "UPDATE producto SET 
                  nombre = :nombre,
                  codigo = :codigo,
                  descripcion = :descripcion,
                  precio = :precio,
                  stock_p = :stock,
                  estado = :estado,
                  id_categoria = :id_categoria
                  WHERE id_producto = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id' => $id,
            ':nombre' => $datos['nombre'],
            ':codigo' => (int)$datos['codigo'],
            ':descripcion' => $datos['descripcion'],
            ':precio' => (float)$datos['precio'],
            ':stock' => (int)$datos['stock'],
            ':estado' => $estadoBool,
            ':id_categoria' => (int)$datos['id_categoria']
        ]);
    }

    // Eliminar producto
    public function eliminar($id) {
        $query = "DELETE FROM producto WHERE id_producto = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
    }

    // Eliminar TODAS las imágenes de un producto
    public function eliminarImagenes($id_producto) {
        $query = "DELETE FROM producto_imagen WHERE id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_producto' => $id_producto]);
    }

    // Eliminar UNA sola imagen por su ID
    public function eliminarImagen($id_imagen) {
        $query = "DELETE FROM producto_imagen WHERE id_imagen = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id_imagen]);
    }

    // Obtener URL de imagen por ID
    public function obtenerUrlImagen($id_imagen) {
        $query = "SELECT url FROM producto_imagen WHERE id_imagen = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id_imagen]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Obtener todas las categorías
    public function obtenerCategorias() {
        $query = "SELECT * FROM categoria_producto ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
}
?>