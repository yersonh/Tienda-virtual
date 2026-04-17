<?php
class PersonaModel {

    private $conn;
    private $table = 'persona';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($data) {
        $query = "INSERT INTO persona (nombres, apellidos, cc, correo, telefono, direccion)
                  VALUES (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)
                  RETURNING id_persona";

        $stmt = $this->conn->prepare($query);

        $stmt->execute([
            ':nombres' => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':cc' => $data['cc'],
            ':correo' => $data['correo'],
            ':telefono' => $data['telefono'],
            ':direccion' => $data['direccion']
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerUltimoId() {
        return $this->conn->lastInsertId();
    }
}
