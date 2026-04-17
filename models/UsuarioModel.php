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

            $queryPersona = "INSERT INTO persona (nombres, apellidos, cc, correo, telefono, direccion)
                             VALUES (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)
                             RETURNING id_persona";

            $stmtPersona = $this->conn->prepare($queryPersona);
            $stmtPersona->execute([
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':cc' => $data['cc'],
                ':correo' => $data['correo'] ?? null,
                ':telefono' => $data['telefono'] ?? null,
                ':direccion' => $data['direccion'] ?? null
            ]);

            $persona = $stmtPersona->fetch(PDO::FETCH_ASSOC);
            $id_persona = (int) ($persona['id_persona'] ?? 0);

            $queryUsuario = "INSERT INTO usuario (id_persona, id_tipo, username, password, estado)
                             VALUES (:id_persona, :id_tipo, :username, :password, :estado)
                             RETURNING id_usuario";

            $stmtUsuario = $this->conn->prepare($queryUsuario);
            $stmtUsuario->execute([
                ':id_persona' => $id_persona,
                ':id_tipo' => $data['id_tipo'],
                ':username' => $data['username'],
                ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':estado' => 'Activo'
            ]);

            $usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);
            $idUsuario = (int) ($usuario['id_usuario'] ?? 0);

            $this->conn->commit();

            return ['success' => true, 'id_usuario' => $idUsuario];

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

    public function ccExiste($cc, $id_persona = null) {
        $query = "SELECT COUNT(*) FROM persona WHERE cc = :cc";

        if ($id_persona) {
            $query .= " AND id_persona != :id_persona";
        }

        $stmt = $this->conn->prepare($query);

        if ($id_persona) {
            $stmt->execute([
                ':cc' => $cc,
                ':id_persona' => $id_persona
            ]);
        } else {
            $stmt->execute([':cc' => $cc]);
        }

        return $stmt->fetchColumn() > 0;
    }

    public function correoExiste($correo, $id_persona) {
        $query = "SELECT COUNT(*) 
                  FROM persona 
                  WHERE correo = :correo 
                  AND id_persona != :id_persona";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':correo' => $correo,
            ':id_persona' => $id_persona
        ]);

        return $stmt->fetchColumn() > 0;
    }

    public function telefonoExiste($telefono, $id_persona) {
        $query = "SELECT COUNT(*) 
                  FROM persona 
                  WHERE telefono = :telefono 
                  AND id_persona != :id_persona";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':telefono' => $telefono,
            ':id_persona' => $id_persona
        ]);

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
     * ACTUALIZAR PERFIL INTELIGENTE
     */
  public function actualizarPerfil($id_usuario, $data) {

        try {

            $usuario = $this->obtenerPorId($id_usuario);

            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }

            $id_persona = $usuario['id_persona'];

            // 🔥 Validar solo si cambian

            if ($data['correo'] !== $usuario['correo']) {
                if ($this->correoExiste($data['correo'], $id_persona)) {
                    return ['success' => false, 'message' => 'El correo ya está en uso'];
                }
            }

            if ($data['telefono'] !== $usuario['telefono']) {
                if ($this->telefonoExiste($data['telefono'], $id_persona)) {
                    return ['success' => false, 'message' => 'El teléfono ya está en uso'];
                }
            }

            // 🔥 UPDATE SIN CC
            $query = "UPDATE persona 
                    SET nombres = :nombres,
                        apellidos = :apellidos,
                        correo = :correo,
                        telefono = :telefono,
                        direccion = :direccion
                    WHERE id_persona = :id_persona";

            $stmt = $this->conn->prepare($query);

            $stmt->execute([
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
