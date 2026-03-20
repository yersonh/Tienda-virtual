<?php
class ProductoModel {

    private $conn;
    private $table = 'producto';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT p.*, c.nombre AS categoria
                  FROM producto p
                  INNER JOIN categoria_producto c ON p.id_categoria = c.id_categoria";

        return $this->conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear($data) {
        $query = "INSERT INTO producto (nombre, codigo, descripcion, precio, stock_p, estado, id_categoria)
                  VALUES (:nombre, :codigo, :descripcion, :precio, :stock, true, :id_categoria)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':nombre' => $data['nombre'],
            ':codigo' => $data['codigo'],
            ':descripcion' => $data['descripcion'],
            ':precio' => $data['precio'],
            ':stock' => $data['stock'],
            ':id_categoria' => $data['id_categoria']
        ]);
    }
}