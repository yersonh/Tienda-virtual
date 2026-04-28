<?php

require_once __DIR__ . '/../config/database.php';

class UsuarioModel {

    private $conn;

    public function __construct($pdo = null) {
        $this->conn = $pdo ?? Database::getConnection();
    }

    // 🔐 LOGIN
    public function validarCredenciales($username, $password) {

        $query = "SELECT 
                    id_usuario,
                    id_persona,
                    id_tipo,
                    username,
                    password,
                    estado,
                    nombres,
                    apellidos,
                    correo,
                    telefono,
                    direccion
                  FROM v_usuario_completo
                  WHERE username = :username";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":username", $username);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            throw new Exception("Error en login: " . $error['message']);
        }

        $row = oci_fetch_assoc($stmt);

        if ($row && password_verify($password, $row['PASSWORD'])) {
            return array_change_key_case($row, CASE_LOWER);
        }

        return null;
    }

    // 👤 OBTENER PERFIL
    public function obtenerPorId($id_usuario) {

        $query = "SELECT 
                    id_usuario,
                    id_persona,
                    id_tipo,
                    username,
                    estado,
                    nombres,
                    apellidos,
                    correo,
                    telefono,
                    direccion
                  FROM v_usuario_completo
                  WHERE id_usuario = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":id_usuario", $id_usuario);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            throw new Exception("Error obteniendo usuario: " . $error['message']);
        }

        $row = oci_fetch_assoc($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    // 📝 REGISTRO (AQUÍ SÍ SE USA CC)
    public function crearConPersona($data) {

        try {

            // Insertar persona
            $sqlPersona = "INSERT INTO persona (
                                id_persona,
                                nombres,
                                apellidos,
                                cc,
                                correo,
                                telefono,
                                direccion
                           ) VALUES (
                                SEQ_PERSONA.NEXTVAL,
                                :nombres,
                                :apellidos,
                                :cc,
                                :correo,
                                :telefono,
                                :direccion
                           ) RETURNING id_persona INTO :id_persona";

            $stmtPersona = oci_parse($this->conn, $sqlPersona);

            oci_bind_by_name($stmtPersona, ":nombres", $data['nombres']);
            oci_bind_by_name($stmtPersona, ":apellidos", $data['apellidos']);
            oci_bind_by_name($stmtPersona, ":cc", $data['cc']);
            oci_bind_by_name($stmtPersona, ":correo", $data['correo']);
            oci_bind_by_name($stmtPersona, ":telefono", $data['telefono']);
            oci_bind_by_name($stmtPersona, ":direccion", $data['direccion']);
            oci_bind_by_name($stmtPersona, ":id_persona", $idPersona, 32);

            if (!oci_execute($stmtPersona, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmtPersona);
                throw new Exception($error['message']);
            }

            // Insertar usuario
            $sqlUsuario = "INSERT INTO usuario (
                                id_usuario,
                                id_persona,
                                id_tipo,
                                username,
                                password,
                                estado
                           ) VALUES (
                                SEQ_USUARIO.NEXTVAL,
                                :id_persona,
                                :id_tipo,
                                :username,
                                :password,
                                'ACTIVO'
                           )";

            $stmtUsuario = oci_parse($this->conn, $sqlUsuario);

            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

            oci_bind_by_name($stmtUsuario, ":id_persona", $idPersona);
            oci_bind_by_name($stmtUsuario, ":id_tipo", $data['id_tipo']);
            oci_bind_by_name($stmtUsuario, ":username", $data['username']);
            oci_bind_by_name($stmtUsuario, ":password", $passwordHash);

            if (!oci_execute($stmtUsuario, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmtUsuario);
                throw new Exception($error['message']);
            }

            oci_commit($this->conn);

            return true;

        } catch (Exception $e) {

            oci_rollback($this->conn);
            throw $e;
        }
    }
}