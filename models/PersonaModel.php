<?php
class PersonaModel {

    private $conn;
    private $table = 'persona';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($data) {
        $query = "INSERT INTO persona (nombres, apellidos, cc, correo, telefono, direccion)
                  VALUES (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':nombres' => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':cc' => $data['cc'],
            ':correo' => $data['correo'],
            ':telefono' => $data['telefono'],
            ':direccion' => $data['direccion']
        ]);
    }

    public function obtenerUltimoId() {
        return $this->conn->lastInsertId();
    }
}