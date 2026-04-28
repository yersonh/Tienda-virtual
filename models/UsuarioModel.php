<?php

require_once __DIR__ . '/../config/database.php';

class UsuarioModel {

    private $conn;

    public function __construct($pdo = null) {
        $this->conn = $pdo ?? Database::getConnection();
    }

    private function oracleErrorResponse(?array $error): array {
        $message = $error['message'] ?? 'Error de Oracle desconocido';

        if (str_contains($message, 'ORA-00001')) {
            $upperMessage = strtoupper($message);

            if (str_contains($upperMessage, 'CORREO')) {
                return ['success' => false, 'error' => 'El correo ya esta registrado', 'message' => 'El correo ya esta registrado'];
            }

            if (str_contains($upperMessage, 'TELEFONO')) {
                return ['success' => false, 'error' => 'El telefono ya esta registrado', 'message' => 'El telefono ya esta registrado'];
            }

            if (str_contains($upperMessage, 'USERNAME') || str_contains($upperMessage, 'USUARIO')) {
                return ['success' => false, 'error' => 'El usuario ya esta registrado', 'message' => 'El usuario ya esta registrado'];
            }

            return ['success' => false, 'error' => 'Ya existe un registro con esos datos', 'message' => 'Ya existe un registro con esos datos'];
        }

        if (str_contains($message, 'ORA-02290')) {
            $upperMessage = strtoupper($message);

            if (str_contains($upperMessage, 'CORREO') || str_contains($upperMessage, 'GMAIL')) {
                return ['success' => false, 'error' => 'Solo se permiten correos @gmail.com', 'message' => 'Solo se permiten correos @gmail.com'];
            }

            if (str_contains($upperMessage, 'TELEFONO')) {
                return ['success' => false, 'error' => 'El telefono debe tener exactamente 10 digitos', 'message' => 'El telefono debe tener exactamente 10 digitos'];
            }

            return ['success' => false, 'error' => 'Los datos no cumplen las reglas de la base de datos', 'message' => 'Los datos no cumplen las reglas de la base de datos'];
        }

        return ['success' => false, 'error' => $message, 'message' => $message];
    }

// 🔐 LOGIN
public function validarCredenciales($username, $password) {

    // 🔥 Normalizar (coherente con índice LOWER en Oracle)
    $username = strtolower(trim($username));

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
              WHERE LOWER(username) = :username";

    $stmt = oci_parse($this->conn, $query);
    oci_bind_by_name($stmt, ":username", $username);

    // 🔴 Manejo seguro de error Oracle
    if (!oci_execute($stmt)) {
        $error = oci_error($stmt);
        oci_free_statement($stmt);
        throw new Exception("Error en login: " . ($error['message'] ?? 'Error desconocido'));
    }

    $row = oci_fetch_assoc($stmt);
    oci_free_statement($stmt);

    // ❌ Usuario no existe
    if (!$row) {
        return null;
    }

    // 🔐 Validación segura del hash
    if (!isset($row['PASSWORD']) || !password_verify($password, $row['PASSWORD'])) {
        return null;
    }

    // ✔ Retorno limpio
    return [
        'id_usuario' => $row['ID_USUARIO'],
        'id_persona' => $row['ID_PERSONA'],
        'id_tipo'    => $row['ID_TIPO'],
        'username'   => $row['USERNAME'],
        'estado'     => $row['ESTADO'],
        'nombres'    => $row['NOMBRES'],
        'apellidos'  => $row['APELLIDOS'],
        'correo'     => $row['CORREO'],
        'telefono'   => $row['TELEFONO'],
        'direccion'  => $row['DIRECCION']
    ];
}

    public function usernameExiste($username): bool {
        $username = strtolower(trim($username));

        $query = "SELECT COUNT(*) AS total
                  FROM usuario
                  WHERE username = :username";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":username", $username);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            throw new Exception("Error verificando usuario: " . ($error['message'] ?? 'desconocido'));
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    public function correoExisteEmail($correo): bool {
        $correo = strtolower(trim($correo));

        $query = "SELECT COUNT(*) AS total
                  FROM persona
                  WHERE LOWER(TRIM(correo)) = LOWER(TRIM(:correo))";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":correo", $correo);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            throw new Exception("Error verificando correo: " . ($error['message'] ?? 'desconocido'));
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    public function correoExiste($correo, $id_persona): bool {
        $correo = strtolower(trim($correo));

        $query = "SELECT COUNT(*) AS total
                  FROM persona
                  WHERE LOWER(TRIM(correo)) = LOWER(TRIM(:correo))
                  AND id_persona != :id_persona";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":correo", $correo);
        oci_bind_by_name($stmt, ":id_persona", $id_persona, -1, SQLT_INT);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            throw new Exception("Error verificando correo: " . ($error['message'] ?? 'desconocido'));
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    public function telefonoExiste($telefono, $id_persona = null): bool {
        $telefono = trim($telefono);

        $query = "SELECT COUNT(*) AS total
                  FROM persona
                  WHERE telefono = :telefono";

        if ($id_persona !== null) {
            $query .= " AND id_persona != :id_persona";
        }

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":telefono", $telefono);

        if ($id_persona !== null) {
            oci_bind_by_name($stmt, ":id_persona", $id_persona, -1, SQLT_INT);
        }

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            throw new Exception("Error verificando telefono: " . ($error['message'] ?? 'desconocido'));
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
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
            $data['correo'] = strtolower(trim($data['correo'] ?? ''));
            $data['username'] = strtolower(trim($data['username'] ?? ''));
            $data['telefono'] = trim($data['telefono'] ?? '');

            // Insertar persona
            $sqlPersona = "INSERT INTO persona (
                                nombres,
                                apellidos,
                                cc,
                                correo,
                                telefono,
                                direccion
                           ) VALUES (
                                :nombres,
                                :apellidos,
                                :cc,
                                :correo,
                                :telefono,
                                :direccion
                           ) RETURNING id_persona INTO :id_persona";

            $stmtPersona = oci_parse($this->conn, $sqlPersona);
            if (!$stmtPersona) {
                return $this->oracleErrorResponse(oci_error($this->conn));
            }

            oci_bind_by_name($stmtPersona, ":nombres", $data['nombres']);
            oci_bind_by_name($stmtPersona, ":apellidos", $data['apellidos']);
            oci_bind_by_name($stmtPersona, ":cc", $data['cc']);
            oci_bind_by_name($stmtPersona, ":correo", $data['correo']);
            oci_bind_by_name($stmtPersona, ":telefono", $data['telefono']);
            oci_bind_by_name($stmtPersona, ":direccion", $data['direccion']);
            $idPersona = null;
            oci_bind_by_name($stmtPersona, ":id_persona", $idPersona, -1, SQLT_INT);

            if (!@oci_execute($stmtPersona, OCI_NO_AUTO_COMMIT)) {
                $response = $this->oracleErrorResponse(oci_error($stmtPersona));
                oci_free_statement($stmtPersona);
                oci_rollback($this->conn);
                return $response;
            }
            oci_free_statement($stmtPersona);

            // Insertar usuario
            $sqlUsuario = "INSERT INTO usuario (
                                id_persona,
                                id_tipo,
                                username,
                                password,
                                estado
                           ) VALUES (
                                :id_persona,
                                :id_tipo,
                                :username,
                                :password,
                                :estado
                           ) RETURNING id_usuario INTO :id_usuario";

            $stmtUsuario = oci_parse($this->conn, $sqlUsuario);
            if (!$stmtUsuario) {
                oci_rollback($this->conn);
                return $this->oracleErrorResponse(oci_error($this->conn));
            }

            $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
            $estado = 'ACTIVO';
            $idUsuario = null;

            oci_bind_by_name($stmtUsuario, ":id_persona", $idPersona);
            oci_bind_by_name($stmtUsuario, ":id_tipo", $data['id_tipo']);
            oci_bind_by_name($stmtUsuario, ":username", $data['username']);
            oci_bind_by_name($stmtUsuario, ":password", $passwordHash);
            oci_bind_by_name($stmtUsuario, ":estado", $estado);
            oci_bind_by_name($stmtUsuario, ":id_usuario", $idUsuario, -1, SQLT_INT);

            if (!@oci_execute($stmtUsuario, OCI_NO_AUTO_COMMIT)) {
                $response = $this->oracleErrorResponse(oci_error($stmtUsuario));
                oci_free_statement($stmtUsuario);
                oci_rollback($this->conn);
                return $response;
            }
            oci_free_statement($stmtUsuario);

            oci_commit($this->conn);

            return ['success' => true, 'id_usuario' => (int) $idUsuario];

        } catch (Exception $e) {

            oci_rollback($this->conn);
            error_log($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()];
        }
    }

    public function actualizarPerfil($id_usuario, $data): array {
        try {
            $usuario = $this->obtenerPorId($id_usuario);

            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }

            $idPersona = (int) $usuario['id_persona'];

            if (($data['correo'] ?? '') !== ($usuario['correo'] ?? '') && $this->correoExiste($data['correo'], $idPersona)) {
                return ['success' => false, 'message' => 'El correo ya esta en uso'];
            }

            if (($data['telefono'] ?? '') !== ($usuario['telefono'] ?? '') && $this->telefonoExiste($data['telefono'], $idPersona)) {
                return ['success' => false, 'message' => 'El telefono ya esta en uso'];
            }

            $query = "UPDATE persona
                      SET nombres = :nombres,
                          apellidos = :apellidos,
                          correo = :correo,
                          telefono = :telefono,
                          direccion = :direccion
                      WHERE id_persona = :id_persona";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ":nombres", $data['nombres']);
            oci_bind_by_name($stmt, ":apellidos", $data['apellidos']);
            oci_bind_by_name($stmt, ":correo", $data['correo']);
            oci_bind_by_name($stmt, ":telefono", $data['telefono']);
            oci_bind_by_name($stmt, ":direccion", $data['direccion']);
            oci_bind_by_name($stmt, ":id_persona", $idPersona, -1, SQLT_INT);

            if (!oci_execute($stmt)) {
                $error = oci_error($stmt);
                throw new Exception($error['message'] ?? 'Error al actualizar perfil');
            }

            oci_free_statement($stmt);

            return ['success' => true];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
