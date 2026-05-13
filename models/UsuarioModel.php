<?php

require_once __DIR__ . '/../config/database.php';

class UsuarioModel
{

    private $conn;

    public function __construct($pdo = null)
    {
        $this->conn = $pdo ?? Database::getConnection();
    }

    private function normalizarCorreo($correo)
    {
        return strtolower(trim((string) $correo));
    }

    private function normalizarUsername($username)
    {
        return strtolower(trim((string) $username));
    }

    private function normalizarTelefono($telefono)
    {
        return preg_replace('/\D/', '', (string) $telefono);
    }

    private function oracleErrorResponse($error): array
    {
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

    private function asegurarClientePorPersona(int $idPersona): ?int
    {
        $query = "SELECT ID_CLIENTE
                  FROM CLIENTE
                  WHERE ID_PERSONA = :id_persona";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":id_persona", $idPersona, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el cliente');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row && isset($row['ID_CLIENTE'])) {
            return (int) $row['ID_CLIENTE'];
        }

        $insert = "INSERT INTO CLIENTE (ID_CLIENTE, ID_PERSONA, FECHA_REGISTRO)
                   VALUES (SEQ_CLIENTE.NEXTVAL, :id_persona, SYSDATE)
                   RETURNING ID_CLIENTE INTO :id_cliente";

        $stmtInsert = oci_parse($this->conn, $insert);
        $idCliente = null;
        oci_bind_by_name($stmtInsert, ":id_persona", $idPersona, -1, SQLT_INT);
        oci_bind_by_name($stmtInsert, ":id_cliente", $idCliente, -1, SQLT_INT);

        if (!@oci_execute($stmtInsert, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmtInsert);
            oci_free_statement($stmtInsert);
            throw new Exception($error['message'] ?? 'No se pudo crear el cliente');
        }

        oci_free_statement($stmtInsert);

        return (int) $idCliente;
    }

    public function validarCredenciales($username, $password)
    {
        $username = $this->normalizarUsername($username);

        // Optimizamos a una sola consulta insensible a mayúsculas/minúsculas
        $query = "SELECT ID_USUARIO,
                         ID_PERSONA,
                         ID_TIPO,
                         USERNAME,
                         PASSWORD,
                         ESTADO
                  FROM USUARIO
                  WHERE LOWER(USERNAME) = :username
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":username", $username);

        if (!oci_execute($stmt)) {
            $error = oci_error($stmt);
            throw new Exception("Error en login: " . ($error['message'] ?? 'desconocido'));
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row || !password_verify($password, $row['PASSWORD'])) {
            return null;
        }

        return [
            'id_usuario' => $row['ID_USUARIO'],
            'id_persona' => $row['ID_PERSONA'],
            'id_tipo' => $row['ID_TIPO'],
            'username' => $row['USERNAME'],
            'estado' => strtoupper(trim($row['ESTADO'])),
            'nombres' => '',
            'apellidos' => '',
            'correo' => '',
            'telefono' => '',
            'direccion' => ''
        ];
    }

    public function usernameExiste($username): bool
    {
        $username = $this->normalizarUsername($username);

        $query = "SELECT COUNT(*) AS total
                  FROM usuario
                  WHERE LOWER(username) = :username";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":username", $username);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    public function correoExisteEmail($correo): bool
    {
        $correo = $this->normalizarCorreo($correo);

        $query = "SELECT COUNT(*) AS total
                  FROM persona
                  WHERE LOWER(TRIM(correo)) = :correo";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":correo", $correo);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    public function correoExiste($correo, $id_persona): bool
    {
        $correo = $this->normalizarCorreo($correo);
        $id_persona = (int) $id_persona;

        $query = "SELECT COUNT(*) AS total
                  FROM persona
                  WHERE LOWER(TRIM(correo)) = :correo
                  AND id_persona != :id_persona";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":correo", $correo);
        oci_bind_by_name($stmt, ":id_persona", $id_persona, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    public function telefonoExiste($telefono, $id_persona = null): bool
    {
        $telefono = $this->normalizarTelefono($telefono);

        $query = "SELECT COUNT(*) AS total
                  FROM persona
                  WHERE telefono = :telefono";

        if ($id_persona !== null) {
            $query .= " AND id_persona != :id_persona";
            $id_persona = (int) $id_persona;
        }

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":telefono", $telefono);

        if ($id_persona !== null) {
            oci_bind_by_name($stmt, ":id_persona", $id_persona, -1, SQLT_INT);
        }

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    public function obtenerPorId($id_usuario)
    {
        $id_usuario = (int) $id_usuario;

        $query = "SELECT id_usuario,
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
        oci_bind_by_name($stmt, ":id_usuario", $id_usuario, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function crearConPersona($data)
    {
        try {
            $data['correo'] = $this->normalizarCorreo($data['correo'] ?? '');
            $data['username'] = $this->normalizarUsername($data['username'] ?? '');
            $data['telefono'] = $this->normalizarTelefono($data['telefono'] ?? '');

            $sqlPersona = "INSERT INTO persona
                (nombres, apellidos, cc, correo, telefono, direccion)
                VALUES
                (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)
                RETURNING id_persona INTO :id_persona";

            $stmt = oci_parse($this->conn, $sqlPersona);
            $idPersona = null;

            oci_bind_by_name($stmt, ":nombres", $data['nombres']);
            oci_bind_by_name($stmt, ":apellidos", $data['apellidos']);
            oci_bind_by_name($stmt, ":cc", $data['cc']);
            oci_bind_by_name($stmt, ":correo", $data['correo']);
            oci_bind_by_name($stmt, ":telefono", $data['telefono']);
            oci_bind_by_name($stmt, ":direccion", $data['direccion']);
            oci_bind_by_name($stmt, ":id_persona", $idPersona, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $response = $this->oracleErrorResponse(oci_error($stmt));
                oci_free_statement($stmt);
                oci_rollback($this->conn);
                return $response;
            }
            oci_free_statement($stmt);

            $sqlUsuario = "INSERT INTO usuario
                (id_persona, id_tipo, username, password, estado)
                VALUES
                (:id_persona, :id_tipo, :username, :password, 'ACTIVO')
                RETURNING id_usuario INTO :id_usuario";

            $stmt2 = oci_parse($this->conn, $sqlUsuario);
            $hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $idUsuario = null;

            oci_bind_by_name($stmt2, ":id_persona", $idPersona, -1, SQLT_INT);
            oci_bind_by_name($stmt2, ":id_tipo", $data['id_tipo'], -1, SQLT_INT);
            oci_bind_by_name($stmt2, ":username", $data['username']);
            oci_bind_by_name($stmt2, ":password", $hash);
            oci_bind_by_name($stmt2, ":id_usuario", $idUsuario, -1, SQLT_INT);

            if (!@oci_execute($stmt2, OCI_NO_AUTO_COMMIT)) {
                $response = $this->oracleErrorResponse(oci_error($stmt2));
                oci_free_statement($stmt2);
                oci_rollback($this->conn);
                return $response;
            }
            oci_free_statement($stmt2);

            if ((int) $data['id_tipo'] === 3) {
                $this->asegurarClientePorPersona((int) $idPersona);
            }

            oci_commit($this->conn);

            return [
                'success' => true,
                'id_usuario' => (int) $idUsuario
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

    public function actualizarPerfil($id_usuario, $data)
    {
        try {
            $id_usuario = (int) $id_usuario;
            $nombres = trim($data['nombres'] ?? '');
            $apellidos = trim($data['apellidos'] ?? '');
            $correo = $this->normalizarCorreo($data['correo'] ?? '');
            $telefono = $this->normalizarTelefono($data['telefono'] ?? '');
            $direccion = trim($data['direccion'] ?? '');

            if ($nombres === '' || $apellidos === '' || $correo === '' || $telefono === '' || $direccion === '') {
                return ['success' => false, 'message' => 'Todos los campos son obligatorios'];
            }

            if (!preg_match('/^[A-Za-z0-9._%+-]+@gmail\.com$/', $correo)) {
                return ['success' => false, 'message' => 'Solo se permiten correos @gmail.com'];
            }

            if (!preg_match('/^[0-9]{10}$/', $telefono)) {
                return ['success' => false, 'message' => 'El teléfono debe tener 10 dígitos'];
            }

            $sql = "SELECT id_persona
                    FROM usuario
                    WHERE id_usuario = :id_usuario";

            $stmt = oci_parse($this->conn, $sql);
            oci_bind_by_name($stmt, ":id_usuario", $id_usuario, -1, SQLT_INT);

            if (!@oci_execute($stmt)) {
                $response = $this->oracleErrorResponse(oci_error($stmt));
                oci_free_statement($stmt);
                return ['success' => false, 'message' => $response['error'] ?? 'Error al consultar el usuario'];
            }

            $usuario = oci_fetch_assoc($stmt);
            oci_free_statement($stmt);

            if (!$usuario) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }

            $idPersona = (int) $usuario['ID_PERSONA'];

            if ($this->correoExiste($correo, $idPersona)) {
                return ['success' => false, 'message' => 'El correo ya está en uso'];
            }

            if ($this->telefonoExiste($telefono, $idPersona)) {
                return ['success' => false, 'message' => 'El teléfono ya está en uso'];
            }

            $sqlUpdate = "UPDATE persona
                          SET nombres = :nombres,
                              apellidos = :apellidos,
                              correo = :correo,
                              telefono = :telefono,
                              direccion = :direccion
                          WHERE id_persona = :id_persona";

            $stmtUpdate = oci_parse($this->conn, $sqlUpdate);
            oci_bind_by_name($stmtUpdate, ":nombres", $nombres);
            oci_bind_by_name($stmtUpdate, ":apellidos", $apellidos);
            oci_bind_by_name($stmtUpdate, ":correo", $correo);
            oci_bind_by_name($stmtUpdate, ":telefono", $telefono);
            oci_bind_by_name($stmtUpdate, ":direccion", $direccion);
            oci_bind_by_name($stmtUpdate, ":id_persona", $idPersona, -1, SQLT_INT);

            if (!@oci_execute($stmtUpdate)) {
                $response = $this->oracleErrorResponse(oci_error($stmtUpdate));
                oci_free_statement($stmtUpdate);
                return ['success' => false, 'message' => $response['error'] ?? 'Error al actualizar el perfil'];
            }

            oci_free_statement($stmtUpdate);

            return ['success' => true];
        } catch (Exception $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar el perfil'];
        }
    }
    //Metodos para recuperar contraseña
    /**
     * Buscar usuario por email
     */
    public function buscarPorEmail($email)
    {
        $email = $this->normalizarCorreo($email);

        $query = "SELECT u.id_usuario, u.username, p.nombres, p.correo
                  FROM usuario u
                  JOIN persona p ON u.id_persona = p.id_persona
                  WHERE LOWER(TRIM(p.correo)) = :correo";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":correo", $email);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ?: null;
    }

    /**
     * Generar token de recuperación
     */
    public function generarTokenRecuperacion($usuario_id)
    {
        $token = bin2hex(random_bytes(32));
        $usuario_id = (int) $usuario_id;

        $sql = "INSERT INTO password_resets (usuario_id, token, expires_at) 
                VALUES (:usuario_id, :token, CURRENT_TIMESTAMP + INTERVAL '1' DAY)";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":usuario_id", $usuario_id, -1, SQLT_INT);
        oci_bind_by_name($stmt, ":token", $token);
        oci_execute($stmt);
        oci_free_statement($stmt);

        return $token;
    }

    /**
     * Validar token de recuperación
     */
    public function validarToken($token)
    {
        $sql = "SELECT pr.usuario_id, u.username, p.nombres, p.correo
                FROM password_resets pr
                JOIN usuario u ON pr.usuario_id = u.id_usuario
                JOIN persona p ON u.id_persona = p.id_persona
                WHERE pr.token = :token 
                AND pr.used = 0 
                AND pr.expires_at > CURRENT_TIMESTAMP";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":token", $token);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ?: null;
    }

    /**
     * Marcar token como usado
     */
    public function marcarTokenUsado($token)
    {
        $sql = "UPDATE password_resets 
                SET used = 1 
                WHERE token = :token";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":token", $token);
        oci_execute($stmt);
        oci_free_statement($stmt);

        return true;
    }

    public function inactivarUsuario(int $idUsuario): bool {
        if ($idUsuario <= 0) return false;

        $sql = "UPDATE USUARIO SET ESTADO = 'INACTIVO', UPDATED_AT = SYSDATE WHERE ID_USUARIO = :id_usuario";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new \Exception($error['message'] ?? 'No se pudo inactivar la cuenta');
        }

        $afectadas = oci_num_rows($stmt);
        oci_free_statement($stmt);
        oci_commit($this->conn);

        return $afectadas > 0;
    }

    /**
     * Actualizar contraseña del usuario
     */
    public function actualizarPassword($id_usuario, $password)
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id_usuario = (int) $id_usuario;

        $sql = "UPDATE usuario 
                SET password = :password 
                WHERE id_usuario = :id_usuario";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ":password", $hash);
        oci_bind_by_name($stmt, ":id_usuario", $id_usuario, -1, SQLT_INT);
        oci_execute($stmt);
        oci_free_statement($stmt);

        return true;
    }
}
