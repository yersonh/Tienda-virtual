<?php

require_once __DIR__ . '/../config/database.php';

class UsuarioModel {

    private $conn;

    public function __construct($pdo = null) {
        $this->conn = $pdo ?? Database::getConnection();
    }

    // 🔥 NORMALIZACIÓN CENTRAL
    private function normalizarCorreo($correo) {
        return strtolower(trim($correo));
    }

    private function normalizarUsername($username) {
        return strtolower(trim($username));
    }

    private function normalizarTelefono($telefono) {
        return preg_replace('/\D/', '', $telefono);
    }

    // 🔥 MANEJO PROFESIONAL DE ERRORES ORACLE
    private function oracleErrorResponse($error): array {

        $message = strtoupper(trim($error['message'] ?? ''));

        if (str_contains($message, 'ORA-00001')) {

            if (str_contains($message, 'CORREO')) {
                return ['success' => false, 'error' => 'El correo ya está registrado'];
            }

            if (str_contains($message, 'TELEFONO')) {
                return ['success' => false, 'error' => 'El teléfono ya está registrado'];
            }

            if (str_contains($message, 'USERNAME') || str_contains($message, 'USUARIO')) {
                return ['success' => false, 'error' => 'El usuario ya está registrado'];
            }

            return ['success' => false, 'error' => 'Ya existe un registro con esos datos'];
        }

        if (str_contains($message, 'ORA-02290')) {

            if (str_contains($message, 'CORREO') || str_contains($message, 'GMAIL')) {
                return ['success' => false, 'error' => 'Solo se permiten correos @gmail.com'];
            }

            if (str_contains($message, 'TELEFONO')) {
                return ['success' => false, 'error' => 'El teléfono debe tener 10 dígitos'];
            }

            return ['success' => false, 'error' => 'Datos inválidos según reglas de BD'];
        }

        return ['success' => false, 'error' => 'Error en base de datos'];
    }

    // 🔐 LOGIN
    public function validarCredenciales($username, $password) {

        $username = $this->normalizarUsername($username);

        $query = "SELECT *
                  FROM v_usuario_completo
                  WHERE LOWER(username) = :username";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":username", $username);

        if (!oci_execute($stmt)) {
            throw new Exception("Error en login");
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) return null;

        if (!password_verify($password, $row['PASSWORD'])) return null;

        return [
            'id_usuario' => $row['ID_USUARIO'],
            'id_persona' => $row['ID_PERSONA'],
            'id_tipo'    => $row['ID_TIPO'],
            'username'   => $row['USERNAME'],
            'estado'     => strtoupper(trim($row['ESTADO'])), // 🔥 FIX
            'nombres'    => $row['NOMBRES'],
            'apellidos'  => $row['APELLIDOS'],
            'correo'     => $row['CORREO'],
            'telefono'   => $row['TELEFONO'],
            'direccion'  => $row['DIRECCION']
        ];
    }

    // 🔍 VALIDACIONES
    public function usernameExiste($username): bool {

        $username = $this->normalizarUsername($username);

        $query = "SELECT COUNT(*) total FROM usuario WHERE LOWER(username) = :username";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":username", $username);

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        return (int)$row['TOTAL'] > 0;
    }

    public function correoExisteEmail($correo): bool {

        $correo = $this->normalizarCorreo($correo);

        $query = "SELECT COUNT(*) total FROM persona WHERE LOWER(correo) = :correo";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":correo", $correo);

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        return (int)$row['TOTAL'] > 0;
    }

    public function telefonoExiste($telefono, $id_persona = null): bool {

        $telefono = $this->normalizarTelefono($telefono);

        $query = "SELECT COUNT(*) total FROM persona WHERE telefono = :telefono";

        if ($id_persona) {
            $query .= " AND id_persona != :id_persona";
        }

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":telefono", $telefono);

        if ($id_persona) {
            oci_bind_by_name($stmt, ":id_persona", $id_persona);
        }

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        return (int)$row['TOTAL'] > 0;
    }

    // 👤 PERFIL
    public function obtenerPorId($id_usuario) {

        $query = "SELECT * FROM v_usuario_completo WHERE id_usuario = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":id", $id_usuario);

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    // 🚀 REGISTRO FULL PROFESIONAL
    public function crearConPersona($data) {

        try {

            // 🔥 NORMALIZAR TODO
            $data['correo']   = $this->normalizarCorreo($data['correo']);
            $data['username'] = $this->normalizarUsername($data['username']);
            $data['telefono'] = $this->normalizarTelefono($data['telefono']);

            // 🔒 BLOQUEO PARA EVITAR DUPLICADOS
            $check = "SELECT 1 FROM persona WHERE correo = :correo OR telefono = :telefono FETCH FIRST 1 ROWS ONLY FOR UPDATE";

            $stmtCheck = oci_parse($this->conn, $check);
            oci_bind_by_name($stmtCheck, ":correo", $data['correo']);
            oci_bind_by_name($stmtCheck, ":telefono", $data['telefono']);

            @oci_execute($stmtCheck, OCI_NO_AUTO_COMMIT);

            // 👤 INSERT PERSONA
            $sqlPersona = "INSERT INTO persona
                (nombres, apellidos, cc, correo, telefono, direccion)
                VALUES
                (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)
                RETURNING id_persona INTO :id_persona";

            $stmt = oci_parse($this->conn, $sqlPersona);

            oci_bind_by_name($stmt, ":nombres", $data['nombres']);
            oci_bind_by_name($stmt, ":apellidos", $data['apellidos']);
            oci_bind_by_name($stmt, ":cc", $data['cc']);
            oci_bind_by_name($stmt, ":correo", $data['correo']);
            oci_bind_by_name($stmt, ":telefono", $data['telefono']);
            oci_bind_by_name($stmt, ":direccion", $data['direccion']);

            $idPersona = null;
            oci_bind_by_name($stmt, ":id_persona", $idPersona, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                oci_rollback($this->conn);
                return $this->oracleErrorResponse(oci_error($stmt));
            }

            // 👤 INSERT USUARIO
            $sqlUsuario = "INSERT INTO usuario
                (id_persona, id_tipo, username, password, estado)
                VALUES
                (:id_persona, :id_tipo, :username, :password, 'ACTIVO')
                RETURNING id_usuario INTO :id_usuario";

            $stmt2 = oci_parse($this->conn, $sqlUsuario);

            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $idUsuario = null;

            oci_bind_by_name($stmt2, ":id_persona", $idPersona);
            oci_bind_by_name($stmt2, ":id_tipo", $data['id_tipo']);
            oci_bind_by_name($stmt2, ":username", $data['username']);
            oci_bind_by_name($stmt2, ":password", $hash);
            oci_bind_by_name($stmt2, ":id_usuario", $idUsuario, -1, SQLT_INT);

            if (!@oci_execute($stmt2, OCI_NO_AUTO_COMMIT)) {
                oci_rollback($this->conn);
                return $this->oracleErrorResponse(oci_error($stmt2));
            }

            oci_commit($this->conn);

            return [
                'success' => true,
                'id_usuario' => (int)$idUsuario
            ];

        } catch (Exception $e) {

            oci_rollback($this->conn);
            error_log($e->getMessage());

            return [
                'success' => false,
                'error' => 'Error interno del sistema'
            ];
        }
    }

    public function actualizarPerfil($id_usuario, $data) {

        try {

            // 🔎 Obtener id_persona
            $sql = "SELECT id_persona FROM usuario WHERE id_usuario = :id_usuario";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':id_usuario' => $id_usuario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ];
            }

            $id_persona = $usuario['id_persona'];

            // 🔎 Validar correo único (excepto el mismo usuario)
            $sqlCorreo = "SELECT COUNT(*) FROM persona 
                        WHERE correo = :correo AND id_persona != :id_persona";
            $stmtCorreo = $this->conn->prepare($sqlCorreo);
            $stmtCorreo->execute([
                ':correo' => $data['correo'],
                ':id_persona' => $id_persona
            ]);

            if ($stmtCorreo->fetchColumn() > 0) {
                return [
                    'success' => false,
                    'message' => 'El correo ya está en uso'
                ];
            }

            // 🔄 Actualizar datos
            $sqlUpdate = "UPDATE persona SET
                            nombres = :nombres,
                            apellidos = :apellidos,
                            correo = :correo,
                            telefono = :telefono,
                            direccion = :direccion
                        WHERE id_persona = :id_persona";

            $stmtUpdate = $this->conn->prepare($sqlUpdate);

            $stmtUpdate->execute([
                ':nombres' => $data['nombres'],
                ':apellidos' => $data['apellidos'],
                ':correo' => $data['correo'],
                ':telefono' => $data['telefono'],
                ':direccion' => $data['direccion'],
                ':id_persona' => $id_persona
            ]);

            return [
                'success' => true
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al actualizar el perfil'
            ];
        }
    }
}