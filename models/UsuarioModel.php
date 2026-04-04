<?php
class UsuarioModel {

    private $conn;
    private $table = 'usuario';

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crear usuario + persona
     */
    public function crearConPersona($data) {
        try {
            $this->conn->beginTransaction();

            // INSERT persona
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

            $id_persona = $this->conn->lastInsertId();

            // INSERT usuario
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

            $id_usuario = $this->conn->lastInsertId();

            $this->conn->commit();

            return ['success' => true];

        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log($e->getMessage());
            return ['success' => false];
        }
    }

    /**
     * LOGIN
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
     * VALIDACIONES
     */

    public function usernameExiste($username) {
        $query = "SELECT COUNT(*) FROM usuario WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':username' => $username]);
        return $stmt->fetchColumn() > 0;
    }

    public function ccExiste($cc) {
        $query = "SELECT COUNT(*) FROM persona WHERE cc = :cc";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':cc' => $cc]);
        return $stmt->fetchColumn() > 0;
    }

    public function correoExiste($correo, $id_persona = null) {
        $query = "SELECT COUNT(*) FROM persona WHERE correo = :correo";

        if ($id_persona) {
            $query .= " AND id_persona != :id_persona";
        }

        $stmt = $this->conn->prepare($query);

        if ($id_persona) {
            $stmt->execute([
                ':correo' => $correo,
                ':id_persona' => $id_persona
            ]);
        } else {
            $stmt->execute([':correo' => $correo]);
        }

        return $stmt->fetchColumn() > 0;
    }

    public function telefonoExiste($telefono, $id_persona = null) {
        $query = "SELECT COUNT(*) FROM persona WHERE telefono = :telefono";

        if ($id_persona) {
            $query .= " AND id_persona != :id_persona";
        }

        $stmt = $this->conn->prepare($query);

        if ($id_persona) {
            $stmt->execute([
                ':telefono' => $telefono,
                ':id_persona' => $id_persona
            ]);
        } else {
            $stmt->execute([':telefono' => $telefono]);
        }

        return $stmt->fetchColumn() > 0;
    }

    /**
     * OBTENER PERFIL
     */
    public function obtenerPorId($id_usuario) {
        $query = "SELECT u.*, p.nombres, p.apellidos, p.cc, p.correo, p.telefono, p.direccion, p.id_persona
                  FROM usuario u
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  WHERE u.id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_usuario' => $id_usuario]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * ACTUALIZAR PERFIL CON VALIDACIONES
     */
    public function actualizarPerfil($id_usuario, $data) {

        try {

            // Obtener persona
            $usuario = $this->obtenerPorId($id_usuario);

            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }

            $id_persona = $usuario['id_persona'];

            // 🔥 VALIDACIONES
            if ($this->correoExiste($data['correo'], $id_persona)) {
                return ['success' => false, 'message' => 'El correo ya está en uso'];
            }

            if ($this->telefonoExiste($data['telefono'], $id_persona)) {
                return ['success' => false, 'message' => 'El teléfono ya está en uso'];
            }

            // UPDATE
            $query = "UPDATE persona 
                      SET cc = :cc,
                          nombres = :nombres,
                          apellidos = :apellidos,
                          correo = :correo,
                          telefono = :telefono,
                          direccion = :direccion
                      WHERE id_persona = :id_persona";

            $stmt = $this->conn->prepare($query);

            $stmt->execute([
                ':cc' => $data['cc'],
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':correo' => $data['correo'],
                ':telefono' => $data['telefono'],
                ':direccion' => $data['direccion'],
                ':id_persona' => $id_persona
            ]);

            return ['success' => true];

        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar'];
        }
    }
}