<?php
class UsuarioModel {

    private $conn;
    private $table = 'usuario';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($data) {
        $query = "INSERT INTO usuario (id_persona, id_tipo, username, password, estado)
                  VALUES (:id_persona, :id_tipo, :username, :password, :estado)";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ':id_persona' => $data['id_persona'],
            ':id_tipo' => $data['id_tipo'],
            ':username' => $data['username'],
            ':password' => $data['password'],
            ':estado' => 'Activo'
        ]);
    }

    public function validarCredenciales($username, $password) {
        $query = "SELECT u.*, p.nombres, p.apellidos
                  FROM usuario u
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  WHERE u.username = :username AND u.password = :password";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':username' => $username,
            ':password' => $password
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}