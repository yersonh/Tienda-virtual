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
            // Transacción automática en OCI8

            $queryPersona = "INSERT INTO persona (nombres, apellidos, cc, correo, telefono, direccion)
                             VALUES (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)
                             RETURNING id_persona INTO :id_persona";

            $stmtPersona = oci_parse($this->conn, $queryPersona);
            oci_bind_by_name($stmtPersona, ':nombres', $data['nombres']);
            oci_bind_by_name($stmtPersona, ':apellidos', $data['apellidos']);
            oci_bind_by_name($stmtPersona, ':cc', $data['cc']);
            oci_bind_by_name($stmtPersona, ':correo', $data['correo'] ?? null);
            oci_bind_by_name($stmtPersona, ':telefono', $data['telefono'] ?? null);
            oci_bind_by_name($stmtPersona, ':direccion', $data['direccion'] ?? null);
            $id_persona = null;
            oci_bind_by_name($stmtPersona, ':id_persona', $id_persona, -1, SQLT_INT);
            oci_execute($stmtPersona);
            oci_fetch($stmtPersona);
            oci_free_statement($stmtPersona);

            $queryUsuario = "INSERT INTO usuario (id_persona, id_tipo, username, password, estado)
                             VALUES (:id_persona, :id_tipo, :username, :password, :estado)
                             RETURNING id_usuario INTO :id_usuario";

            $stmtUsuario = oci_parse($this->conn, $queryUsuario);
            oci_bind_by_name($stmtUsuario, ':id_persona', $id_persona, -1, SQLT_INT);
            oci_bind_by_name($stmtUsuario, ':id_tipo', $data['id_tipo'], -1, SQLT_INT);
            oci_bind_by_name($stmtUsuario, ':username', $data['username']);
            oci_bind_by_name($stmtUsuario, ':password', password_hash($data['password'], PASSWORD_DEFAULT));
            oci_bind_by_name($stmtUsuario, ':estado', 'Activo');
            $id_usuario = null;
            oci_bind_by_name($stmtUsuario, ':id_usuario', $id_usuario, -1, SQLT_INT);
            oci_execute($stmtUsuario);
            oci_fetch($stmtUsuario);
            oci_free_statement($stmtUsuario);

            oci_commit($this->conn);

            return ['success' => true, 'id_usuario' => (int)$id_usuario];

        } catch (Exception $e) {
            oci_rollback($this->conn);
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

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':username', $username);
        oci_execute($stmt);

        $usuario = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($usuario && password_verify($password, $usuario['PASSWORD'])) {
            return array_change_key_case($usuario, CASE_LOWER);
        }

        return false;
    }

    /**
     * VALIDACIONES
     */

    public function usernameExiste($username) {
        $query = "SELECT COUNT(*) as count FROM usuario WHERE username = :username";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':username', $username);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)$row['COUNT'] > 0;
    }

    public function correoExisteEmail($correo) {
        $query = "SELECT COUNT(*) as count FROM persona WHERE LOWER(TRIM(correo)) = LOWER(TRIM(:correo))";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':correo', $correo);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int)$row['COUNT'] > 0;
    }

    public function ccExiste($cc, $id_persona = null) {
        $query = "SELECT COUNT(*) as count FROM persona WHERE cc = :cc";

        if ($id_persona) {
            $query .= " AND id_persona != :id_persona";
        }

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':cc', $cc);

        if ($id_persona) {
            oci_bind_by_name($stmt, ':id_persona', $id_persona, -1, SQLT_INT);
        }

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int)$row['COUNT'] > 0;
    }

    public function correoExiste($correo, $id_persona) {
        $query = "SELECT COUNT(*) as count
                  FROM persona 
                  WHERE correo = :correo 
                  AND id_persona != :id_persona";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':correo', $correo);
        oci_bind_by_name($stmt, ':id_persona', $id_persona, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int)$row['COUNT'] > 0;
    }

    public function telefonoExiste($telefono, $id_persona) {
        $query = "SELECT COUNT(*) as count
                  FROM persona 
                  WHERE telefono = :telefono 
                  AND id_persona != :id_persona";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':telefono', $telefono);
        oci_bind_by_name($stmt, ':id_persona', $id_persona, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int)$row['COUNT'] > 0;
    }

    /**
     * OBTENER PERFIL
     */
    public function obtenerPorId($id_usuario) {
        $query = "SELECT u.*, p.nombres, p.apellidos, p.cc, p.correo, p.telefono, p.direccion, p.id_persona
                  FROM usuario u
                  INNER JOIN persona p ON u.id_persona = p.id_persona
                  WHERE u.id_usuario = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $id_usuario, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
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

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':nombres', $data['nombres']);
            oci_bind_by_name($stmt, ':apellidos', $data['apellidos']);
            oci_bind_by_name($stmt, ':correo', $data['correo']);
            oci_bind_by_name($stmt, ':telefono', $data['telefono']);
            oci_bind_by_name($stmt, ':direccion', $data['direccion']);
            oci_bind_by_name($stmt, ':id_persona', $id_persona, -1, SQLT_INT);
            oci_execute($stmt);
            oci_free_statement($stmt);

            return ['success' => true];

        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar'];
        }
    }
}
