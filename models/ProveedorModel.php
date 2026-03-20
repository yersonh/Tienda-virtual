<?php
class ProveedorModel {

    private $conn;
    private $table = 'proveedor';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        return $this->conn->query("SELECT * FROM proveedor")
                          ->fetchAll(PDO::FETCH_ASSOC);
    }
}