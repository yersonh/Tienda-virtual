<?php
class TipoUsuarioModel {

    private $conn;
    private $table = 'tipo_usuario';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        return $this->conn->query("SELECT * FROM tipo_usuario")
                          ->fetchAll(PDO::FETCH_ASSOC);
    }
}