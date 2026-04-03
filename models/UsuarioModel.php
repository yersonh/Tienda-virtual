<?php
class UsuarioModel {

    private $conn;
    private $table = 'usuario';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crea una persona y luego un usuario
     */
    public function crearConPersona($data) {
        try {
            // Iniciar transacción
            $this->conn->beginTransaction();

            // 1. INSERT en tabla persona (SIN RETURNING)
            $queryPersona = "INSERT INTO persona (nombres, apellidos, cc, correo, telefono, direccion)
                            VALUES (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)";

            $stmtPersona = $this->conn->prepare($queryPersona);
            $stmtPersona->execute([
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':cc' => $data['cc'],
                ':correo' => $data['correo'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':direccion' => $data['direccion'] ?? null
            ]);

            // Obtener ID de persona (compatible con Railway)
            $id_persona = $this->conn->lastInsertId();

            // 2. INSERT en usuario
            $queryUsuario = "INSERT INTO usuario (id_persona, id_tipo, username, password, estado)
                             VALUES (:id_persona, :id_tipo, :username, :password, :estado)";

            $stmtUsuario = $this->conn->prepare($queryUsuario);
            $stmtUsuario->execute([
                ':id_persona' => $id_persona,
                ':id_tipo' => $data['id_tipo'],
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':estado' => 'Activo'
            ]);

            // Obtener ID usuario
            $id_usuario = $this->conn->lastInsertId();

            // Confirmar transacción
            $this->conn->commit();

            return [
                'success' => true,
                'id_usuario' => $id_usuario,
                'id_persona' => $id_persona,
                'message' => 'Usuario creado exitosamente'
            ];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al crear usuario: " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Error al crear el usuario'
            ];
        }
    }

    /**
     * Validar login
     */
    public function validarCredenciales($username, $password) {

        $query = "SELECT u.*, p.nombres, p.apellidos, p.cc, p.correo, p.telefono, p.direccion
                  FROM usuario u
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  WHERE u.username = :username";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':username' => $username]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password'])) {
            return $usuario;
        }

        return false;
    }

    /**
     * Verificar si username existe
     */
    public function usernameExiste($username) {
        $query = "SELECT COUNT(*) FROM usuario WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Verificar si CC existe
     */
    public function ccExiste($cc) {
        $query = "SELECT COUNT(*) FROM persona WHERE cc = :cc";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':cc' => $cc]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Obtener usuario por ID
     */
    public function obtenerPorId($id_usuario) {
        $query = "SELECT u.*, p.nombres, p.apellidos, p.cc, p.correo, p.telefono, p.direccion
                  FROM usuario u
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  WHERE u.id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_usuario' => $id_usuario]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}