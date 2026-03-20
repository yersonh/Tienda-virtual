<?php
class CategoriaModel {

    private $conn;
    private $table = 'categoria_producto';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodas() {
        return $this->conn->query("SELECT * FROM categoria_producto")
                          ->fetchAll(PDO::FETCH_ASSOC);
    }
}