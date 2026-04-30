<?php

class DireccionPedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function desactivarDmlParalelo(): void {
        $stmt = oci_parse($this->conn, 'ALTER SESSION DISABLE PARALLEL DML');
        if ($stmt) {
            @oci_execute($stmt);
            oci_free_statement($stmt);
        }
    }

    private function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function normalizarFila(array $row): array {
        $data = array_change_key_case($row, CASE_LOWER);

        if (isset($data['id_direccion'])) {
            $data['id_direccion_pedido'] = $data['id_direccion'];
        }
        if (isset($data['nombre'])) {
            $data['nombre_receptor'] = $data['nombre'];
        }
        if (isset($data['apellido'])) {
            $data['apellido_receptor'] = $data['apellido'];
        }
        if (isset($data['direccion'])) {
            $data['direccion_envio'] = $data['direccion'];
        }
        if (isset($data['telefono'])) {
            $data['telefono_receptor'] = $data['telefono'];
        }
        if (isset($data['telefono_alt'])) {
            $data['telefono_alterno'] = $data['telefono_alt'];
        }
        if (isset($data['info_adicional'])) {
            $data['informacion_adicional'] = $data['info_adicional'];
        }

        $data['es_predeterminada'] = (int) ($data['es_predeterminada'] ?? 0);

        return $data;
    }

    private function limpiarCache(int $idUsuario): void {
        $this->ensureSession();
        unset($_SESSION['direcciones'], $_SESSION['direcciones_usuario_id']);
    }

    private function tablaTieneColumna(string $tabla, string $columna): bool {
        $this->ensureSession();
        $tabla = strtoupper($tabla);
        $columna = strtoupper($columna);
        $cacheKey = $tabla . '.' . $columna;

        if (isset($_SESSION['schema_column_cache'][$cacheKey])) {
            return (bool) $_SESSION['schema_column_cache'][$cacheKey];
        }

        $query = "SELECT 1
                  FROM USER_TAB_COLUMNS
                  WHERE TABLE_NAME = :tabla
                  AND COLUMN_NAME = :columna
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':tabla', $tabla);
        oci_bind_by_name($stmt, ':columna', $columna);
        oci_execute($stmt);

        $existe = (bool) oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        $_SESSION['schema_column_cache'][$cacheKey] = $existe;
        return $existe;
    }

    private function validarDireccion(array $data): array {
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        $nombre = trim($data['nombre_receptor'] ?? $data['nombre'] ?? '');
        $apellido = trim($data['apellido_receptor'] ?? $data['apellido'] ?? '');
        $direccion = trim($data['direccion_envio'] ?? $data['direccion'] ?? '');
        $ciudad = trim($data['ciudad'] ?? '');
        $barrio = trim($data['barrio'] ?? '');
        $telefono = preg_replace('/\D/', '', (string) ($data['telefono_receptor'] ?? $data['telefono'] ?? ''));
        $telefonoAlt = preg_replace('/\D/', '', (string) ($data['telefono_alterno'] ?? $data['telefono_alt'] ?? ''));
        $info = trim($data['informacion_adicional'] ?? $data['info_adicional'] ?? '');
        $predeterminada = !empty($data['es_predeterminada']) ? 1 : 0;

        if ($idUsuario <= 0 || $nombre === '' || $apellido === '' || $direccion === '' || $ciudad === '' || $barrio === '' || $telefono === '') {
            throw new InvalidArgumentException('Todos los campos de direccion son obligatorios');
        }

        return compact('idUsuario', 'nombre', 'apellido', 'direccion', 'ciudad', 'barrio', 'telefono', 'telefonoAlt', 'info', 'predeterminada');
    }

    private function usuarioTieneDirecciones(int $idUsuario): bool {
        $query = "SELECT COUNT(*) AS TOTAL
                  FROM DIRECCION_USUARIO
                  WHERE ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL'] ?? 0) > 0;
    }

    private function quitarPredeterminada(int $idUsuario): void {
        $this->desactivarDmlParalelo();

        $query = "UPDATE /*+ NO_PARALLEL(DIRECCION_USUARIO) */ DIRECCION_USUARIO
                  SET ES_PREDETERMINADA = 0
                  WHERE ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            error_log($error['message'] ?? 'No se pudo actualizar la direccion predeterminada');
            throw new Exception('No se pudo actualizar la direccion predeterminada');
        }

        oci_free_statement($stmt);
    }

    public function obtenerDirecciones($idUsuario): array {
        $idUsuario = (int) $idUsuario;
        $this->ensureSession();

        if (
            isset($_SESSION['direcciones'], $_SESSION['direcciones_usuario_id'])
            && (int) $_SESSION['direcciones_usuario_id'] === $idUsuario
            && is_array($_SESSION['direcciones'])
        ) {
            return $_SESSION['direcciones'];
        }

        $query = "SELECT *
                  FROM DIRECCION_USUARIO
                  WHERE ID_USUARIO = :id_usuario
                  ORDER BY ES_PREDETERMINADA DESC, ID_DIRECCION DESC";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $direcciones = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $direcciones[] = $this->normalizarFila($row);
        }

        oci_free_statement($stmt);
        $_SESSION['direcciones'] = $direcciones;
        $_SESSION['direcciones_usuario_id'] = $idUsuario;

        return $direcciones;
    }

    public function obtenerDireccionPorId($idDireccion, $idUsuario = null): ?array {
        $idDireccion = (int) $idDireccion;
        $idUsuario = $idUsuario !== null ? (int) $idUsuario : null;

        $query = "SELECT ID_DIRECCION,
                         ID_USUARIO,
                         NOMBRE,
                         APELLIDO,
                         DIRECCION,
                         CIUDAD,
                         BARRIO,
                         TELEFONO,
                         TELEFONO_ALT,
                         INFO_ADICIONAL,
                         NVL(ES_PREDETERMINADA, 0) AS ES_PREDETERMINADA,
                         CREATED_AT
                  FROM DIRECCION_USUARIO
                  WHERE ID_DIRECCION = :id_direccion";

        if ($idUsuario !== null) {
            $query .= " AND ID_USUARIO = :id_usuario";
        }

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_direccion', $idDireccion, -1, SQLT_INT);
        if ($idUsuario !== null) {
            oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        }

        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->normalizarFila($row) : null;
    }

    public function guardarDireccion($data): array {
        try {
            $this->desactivarDmlParalelo();
            $d = $this->validarDireccion($data);
            $tieneDirecciones = $this->usuarioTieneDirecciones($d['idUsuario']);
            $d['predeterminada'] = ($d['predeterminada'] === 1 || !$tieneDirecciones) ? 1 : 0;

            if ($d['predeterminada'] === 1 && $tieneDirecciones) {
                $this->quitarPredeterminada($d['idUsuario']);
            }

            $query = "INSERT /*+ NO_PARALLEL(DIRECCION_USUARIO) */ INTO DIRECCION_USUARIO
                        (ID_USUARIO, NOMBRE, APELLIDO, DIRECCION, CIUDAD, BARRIO, TELEFONO, TELEFONO_ALT, INFO_ADICIONAL, ES_PREDETERMINADA, CREATED_AT)
                      VALUES
                        (:id_usuario, :nombre, :apellido, :direccion, :ciudad, :barrio, :telefono, :telefono_alt, :info, :predeterminada, SYSDATE)
                      RETURNING ID_DIRECCION INTO :id_direccion";

            $stmt = oci_parse($this->conn, $query);
            $idDireccion = null;

            oci_bind_by_name($stmt, ':id_usuario', $d['idUsuario'], -1, SQLT_INT);
            oci_bind_by_name($stmt, ':nombre', $d['nombre']);
            oci_bind_by_name($stmt, ':apellido', $d['apellido']);
            oci_bind_by_name($stmt, ':direccion', $d['direccion']);
            oci_bind_by_name($stmt, ':ciudad', $d['ciudad']);
            oci_bind_by_name($stmt, ':barrio', $d['barrio']);
            oci_bind_by_name($stmt, ':telefono', $d['telefono']);
            oci_bind_by_name($stmt, ':telefono_alt', $d['telefonoAlt']);
            oci_bind_by_name($stmt, ':info', $d['info']);
            oci_bind_by_name($stmt, ':predeterminada', $d['predeterminada'], -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_direccion', $idDireccion, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                oci_rollback($this->conn);
                error_log($error['message'] ?? 'No se pudo guardar la direccion');
                return ['success' => false, 'message' => 'No se pudo guardar la direccion'];
            }

            oci_free_statement($stmt);
            oci_commit($this->conn);
            $this->limpiarCache($d['idUsuario']);

            $direccion = $this->normalizarFila([
                'ID_DIRECCION' => (int) $idDireccion,
                'ID_USUARIO' => $d['idUsuario'],
                'NOMBRE' => $d['nombre'],
                'APELLIDO' => $d['apellido'],
                'DIRECCION' => $d['direccion'],
                'CIUDAD' => $d['ciudad'],
                'BARRIO' => $d['barrio'],
                'TELEFONO' => $d['telefono'],
                'TELEFONO_ALT' => $d['telefonoAlt'],
                'INFO_ADICIONAL' => $d['info'],
                'ES_PREDETERMINADA' => $d['predeterminada']
            ]);

            return [
                'success' => true,
                'id_direccion' => (int) $idDireccion,
                'direccion' => $direccion
            ];
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());

            return ['success' => false, 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo guardar la direccion'];
        }
    }

    public function actualizarDireccion(int $idDireccion, int $idUsuario, array $data): array {
        try {
            $this->desactivarDmlParalelo();
            $data['id_usuario'] = $idUsuario;
            $d = $this->validarDireccion($data);

            if (!$this->obtenerDireccionPorId($idDireccion, $idUsuario)) {
                return ['success' => false, 'message' => 'La direccion no existe'];
            }

            if ($d['predeterminada'] === 1) {
                $this->quitarPredeterminada($idUsuario);
            }

            $query = "UPDATE /*+ NO_PARALLEL(DIRECCION_USUARIO) */ DIRECCION_USUARIO
                      SET NOMBRE = :nombre,
                          APELLIDO = :apellido,
                          DIRECCION = :direccion,
                          CIUDAD = :ciudad,
                          BARRIO = :barrio,
                          TELEFONO = :telefono,
                          TELEFONO_ALT = :telefono_alt,
                          INFO_ADICIONAL = :info,
                          ES_PREDETERMINADA = CASE WHEN :predeterminada = 1 THEN 1 ELSE ES_PREDETERMINADA END
                      WHERE ID_DIRECCION = :id_direccion
                      AND ID_USUARIO = :id_usuario";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':nombre', $d['nombre']);
            oci_bind_by_name($stmt, ':apellido', $d['apellido']);
            oci_bind_by_name($stmt, ':direccion', $d['direccion']);
            oci_bind_by_name($stmt, ':ciudad', $d['ciudad']);
            oci_bind_by_name($stmt, ':barrio', $d['barrio']);
            oci_bind_by_name($stmt, ':telefono', $d['telefono']);
            oci_bind_by_name($stmt, ':telefono_alt', $d['telefonoAlt']);
            oci_bind_by_name($stmt, ':info', $d['info']);
            oci_bind_by_name($stmt, ':predeterminada', $d['predeterminada'], -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_direccion', $idDireccion, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                oci_rollback($this->conn);
                error_log($error['message'] ?? 'No se pudo actualizar la direccion');
                return ['success' => false, 'message' => 'No se pudo actualizar la direccion'];
            }

            oci_free_statement($stmt);
            oci_commit($this->conn);
            $this->limpiarCache($idUsuario);

            return [
                'success' => true,
                'direccion' => $this->obtenerDireccionPorId($idDireccion, $idUsuario)
            ];
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());

            return ['success' => false, 'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo actualizar la direccion'];
        }
    }

    public function eliminarDireccion(int $idDireccion, int $idUsuario): array {
        try {
            $this->desactivarDmlParalelo();
            $direccion = $this->obtenerDireccionPorId($idDireccion, $idUsuario);
            if (!$direccion) {
                return ['success' => false, 'message' => 'La direccion no existe'];
            }

            $queryCount = "SELECT COUNT(*) AS TOTAL
                           FROM DIRECCION_USUARIO
                           WHERE ID_USUARIO = :id_usuario";
            $stmtCount = oci_parse($this->conn, $queryCount);
            oci_bind_by_name($stmtCount, ':id_usuario', $idUsuario, -1, SQLT_INT);
            oci_execute($stmtCount);
            $rowCount = oci_fetch_assoc($stmtCount);
            oci_free_statement($stmtCount);

            if ((int) ($rowCount['TOTAL'] ?? 0) <= 1) {
                return ['success' => false, 'message' => 'No puedes eliminar tu unica direccion'];
            }

            $queryDelete = "DELETE /*+ NO_PARALLEL(DIRECCION_USUARIO) */ FROM DIRECCION_USUARIO
                            WHERE ID_DIRECCION = :id_direccion
                            AND ID_USUARIO = :id_usuario";
            $stmtDelete = oci_parse($this->conn, $queryDelete);
            oci_bind_by_name($stmtDelete, ':id_direccion', $idDireccion, -1, SQLT_INT);
            oci_bind_by_name($stmtDelete, ':id_usuario', $idUsuario, -1, SQLT_INT);

            if (!@oci_execute($stmtDelete, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmtDelete);
                oci_free_statement($stmtDelete);
                oci_rollback($this->conn);
                error_log($error['message'] ?? 'No se pudo eliminar la direccion');
                return ['success' => false, 'message' => 'No se pudo eliminar la direccion'];
            }
            oci_free_statement($stmtDelete);

            if ((int) $direccion['es_predeterminada'] === 1) {
                $queryDefault = "UPDATE /*+ NO_PARALLEL(DIRECCION_USUARIO) */ DIRECCION_USUARIO
                                 SET ES_PREDETERMINADA = 1
                                 WHERE ID_DIRECCION = (
                                     SELECT MIN(ID_DIRECCION)
                                     FROM DIRECCION_USUARIO
                                     WHERE ID_USUARIO = :id_usuario
                                 )";
                $stmtDefault = oci_parse($this->conn, $queryDefault);
                oci_bind_by_name($stmtDefault, ':id_usuario', $idUsuario, -1, SQLT_INT);

                if (!@oci_execute($stmtDefault, OCI_NO_AUTO_COMMIT)) {
                    $error = oci_error($stmtDefault);
                    oci_free_statement($stmtDefault);
                    oci_rollback($this->conn);
                    error_log($error['message'] ?? 'No se pudo asignar una direccion predeterminada');
                    return ['success' => false, 'message' => 'No se pudo asignar una direccion predeterminada'];
                }
                oci_free_statement($stmtDefault);
            }

            oci_commit($this->conn);
            $this->limpiarCache($idUsuario);

            return [
                'success' => true,
                'direcciones' => $this->obtenerDirecciones($idUsuario)
            ];
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());

            return ['success' => false, 'message' => 'No se pudo eliminar la direccion'];
        }
    }

    public function copiarDireccionParaPedido(int $idPedido, int $idDireccion, int $idUsuario, ?array $direccion = null): int {
        $direccion = $direccion ?: $this->obtenerDireccionPorId($idDireccion, $idUsuario);
        if (!$direccion || (int) ($direccion['id_usuario'] ?? 0) !== $idUsuario) {
            throw new Exception('Direccion de usuario invalida');
        }

        $nombre = (string) ($direccion['nombre_receptor'] ?? '');
        $apellido = (string) ($direccion['apellido_receptor'] ?? '');
        $direccionEnvio = (string) ($direccion['direccion_envio'] ?? '');
        $ciudad = (string) ($direccion['ciudad'] ?? '');
        $barrio = (string) ($direccion['barrio'] ?? '');
        $telefono = (string) ($direccion['telefono_receptor'] ?? '');
        $telefonoAlt = (string) ($direccion['telefono_alterno'] ?? '');
        $info = (string) ($direccion['informacion_adicional'] ?? '');
        $esPredeterminada = (int) ($direccion['es_predeterminada'] ?? 0) === 1 ? 1 : 0;
        $guardarPredeterminadaPedido = $this->tablaTieneColumna('DIRECCION_PEDIDO', 'ES_PREDETERMINADA');

        $columnas = "ID_PEDIDO, NOMBRE_RECEPTOR, APELLIDO_RECEPTOR, DIRECCION_ENVIO, CIUDAD, BARRIO, TELEFONO_RECEPTOR, TELEFONO_ALTERNO, INFORMACION_ADICIONAL, CREATED_AT, UPDATED_AT";
        $valores = ":id_pedido, :nombre, :apellido, :direccion, :ciudad, :barrio, :telefono, :telefono_alt, :info, SYSDATE, SYSDATE";

        if ($guardarPredeterminadaPedido) {
            $columnas .= ", ES_PREDETERMINADA";
            $valores .= ", :es_predeterminada";
        }

        $queryInsert = "INSERT INTO DIRECCION_PEDIDO
                            ($columnas)
                        VALUES
                            ($valores)
                        RETURNING ID_DIRECCION_PEDIDO INTO :id_direccion_pedido";

        $stmtInsert = oci_parse($this->conn, $queryInsert);
        $idDireccionPedido = null;

        oci_bind_by_name($stmtInsert, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmtInsert, ':nombre', $nombre);
        oci_bind_by_name($stmtInsert, ':apellido', $apellido);
        oci_bind_by_name($stmtInsert, ':direccion', $direccionEnvio);
        oci_bind_by_name($stmtInsert, ':ciudad', $ciudad);
        oci_bind_by_name($stmtInsert, ':barrio', $barrio);
        oci_bind_by_name($stmtInsert, ':telefono', $telefono);
        oci_bind_by_name($stmtInsert, ':telefono_alt', $telefonoAlt);
        oci_bind_by_name($stmtInsert, ':info', $info);
        if ($guardarPredeterminadaPedido) {
            oci_bind_by_name($stmtInsert, ':es_predeterminada', $esPredeterminada, -1, SQLT_INT);
        }
        oci_bind_by_name($stmtInsert, ':id_direccion_pedido', $idDireccionPedido, -1, SQLT_INT);

        if (!@oci_execute($stmtInsert, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmtInsert);
            oci_free_statement($stmtInsert);
            error_log($error['message'] ?? 'No se pudo copiar la direccion del pedido');
            throw new Exception('No se pudo copiar la direccion del pedido');
        }
        oci_free_statement($stmtInsert);
        oci_commit($this->conn);
        if ((int) $idDireccionPedido <= 0) {
            throw new Exception('No se pudo obtener la direccion del pedido');
        }

        return $idDireccionPedido;
    }

    public function actualizarDireccionPedidoPendiente(int $idPedido, array $data): array {
        $nombre = trim($data['nombre_receptor'] ?? '');
        $apellido = trim($data['apellido_receptor'] ?? '');
        $direccion = trim($data['direccion_envio'] ?? '');
        $ciudad = trim($data['ciudad'] ?? '');
        $barrio = trim($data['barrio'] ?? '');
        $telefono = preg_replace('/\D/', '', (string) ($data['telefono_receptor'] ?? ''));
        $telefonoAlt = preg_replace('/\D/', '', (string) ($data['telefono_alterno'] ?? ''));
        $info = trim($data['informacion_adicional'] ?? '');

        if ($idPedido <= 0 || $nombre === '' || $apellido === '' || $direccion === '' || $ciudad === '' || $barrio === '' || $telefono === '') {
            return ['success' => false, 'message' => 'Todos los campos de direccion son obligatorios'];
        }

        $query = "UPDATE DIRECCION_PEDIDO dp
                  SET dp.NOMBRE_RECEPTOR = :nombre,
                      dp.APELLIDO_RECEPTOR = :apellido,
                      dp.DIRECCION_ENVIO = :direccion,
                      dp.CIUDAD = :ciudad,
                      dp.BARRIO = :barrio,
                      dp.TELEFONO_RECEPTOR = :telefono,
                      dp.TELEFONO_ALTERNO = :telefono_alt,
                      dp.INFORMACION_ADICIONAL = :info,
                      dp.UPDATED_AT = SYSDATE
                  WHERE dp.ID_PEDIDO = :id_pedido
                  AND EXISTS (
                      SELECT 1
                      FROM PEDIDO p
                      WHERE p.ID_PEDIDO = dp.ID_PEDIDO
                      AND p.ID_ESTADO = 1
                  )";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':nombre', $nombre);
        oci_bind_by_name($stmt, ':apellido', $apellido);
        oci_bind_by_name($stmt, ':direccion', $direccion);
        oci_bind_by_name($stmt, ':ciudad', $ciudad);
        oci_bind_by_name($stmt, ':barrio', $barrio);
        oci_bind_by_name($stmt, ':telefono', $telefono);
        oci_bind_by_name($stmt, ':telefono_alt', $telefonoAlt);
        oci_bind_by_name($stmt, ':info', $info);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            error_log($error['message'] ?? 'No se pudo actualizar la direccion del pedido');
            return ['success' => false, 'message' => 'No se pudo actualizar la direccion del pedido'];
        }

        $rows = oci_num_rows($stmt);
        oci_free_statement($stmt);

        if ($rows <= 0) {
            return ['success' => false, 'message' => 'Solo puedes editar la direccion de pedidos pendientes'];
        }

        return ['success' => true];
    }
}
