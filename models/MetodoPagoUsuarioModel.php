<?php

class MetodoPagoUsuarioModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    private function normalizarFila(array $row): array {
        return array_change_key_case($row, CASE_LOWER);
    }

    public function obtenerPorUsuario(int $idUsuario): array {
        if ($idUsuario <= 0) {
            return [];
        }

        $query = "SELECT ID_METODO_PAGO_USUARIO,
                         ID_USUARIO,
                         ID_METODO,
                         FORMA_PAGO,
                         TITULAR,
                         ULTIMOS_4,
                         FRANQUICIA,
                         FECHA_EXPIRACION_TEXTO,
                         ES_PREDETERMINADO,
                         ACTIVO,
                         TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION
                  FROM V_METODOS_PAGO_USUARIO
                  WHERE ID_USUARIO = :id_usuario
                  ORDER BY ES_PREDETERMINADO DESC, ID_METODO_PAGO_USUARIO DESC";

        $stmt = @oci_parse($this->conn, $query);
        if (!$stmt) {
            return [];
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_free_statement($stmt);
            return [];
        }

        $metodos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $metodos[] = $this->normalizarFila($row);
        }

        oci_free_statement($stmt);
        return $metodos;
    }

    public function obtenerPredeterminado(int $idUsuario): ?array {
        if ($idUsuario <= 0) {
            return null;
        }

        $query = "SELECT ID_METODO_PAGO_USUARIO,
                         ID_USUARIO,
                         ID_METODO,
                         FORMA_PAGO,
                         TITULAR,
                         ULTIMOS_4,
                         FRANQUICIA,
                         FECHA_EXPIRACION_TEXTO,
                         ES_PREDETERMINADO,
                         ACTIVO,
                         TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION
                  FROM V_METODOS_PAGO_USUARIO
                  WHERE ID_USUARIO = :id_usuario
                    AND ES_PREDETERMINADO = 1
                  ORDER BY ID_METODO_PAGO_USUARIO DESC
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = @oci_parse($this->conn, $query);
        if (!$stmt) {
            return null;
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_free_statement($stmt);
            return null;
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->normalizarFila($row) : null;
    }

    public function obtenerPorIdUsuario(int $idMetodoPagoUsuario, int $idUsuario): ?array {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return null;
        }

        $query = "SELECT ID_METODO_PAGO_USUARIO,
                         ID_USUARIO,
                         ID_METODO,
                         FORMA_PAGO,
                         TITULAR,
                         ULTIMOS_4,
                         FRANQUICIA,
                         FECHA_EXPIRACION_TEXTO,
                         ES_PREDETERMINADO,
                         ACTIVO,
                         TO_CHAR(FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS') AS FECHA_CREACION
                  FROM V_METODOS_PAGO_USUARIO
                  WHERE ID_METODO_PAGO_USUARIO = :id_metodo_pago_usuario
                    AND ID_USUARIO = :id_usuario
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_metodo_pago_usuario', $idMetodoPagoUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->normalizarFila($row) : null;
    }

    public function guardar(array $data): int {
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        $idMetodo = (int) ($data['id_metodo'] ?? 0);
        $titular = trim((string) ($data['titular'] ?? ''));
        $ultimos4 = preg_replace('/\D+/', '', (string) ($data['ultimos_4'] ?? ''));
        $franquicia = strtoupper(trim((string) ($data['franquicia'] ?? '')));
        $tokenPago = trim((string) ($data['token_pago'] ?? ''));
        $fechaExpiracion = trim((string) ($data['fecha_expiracion'] ?? ''));
        $esPredeterminado = !empty($data['es_predeterminado']) ? 1 : 0;

        if ($idUsuario <= 0 || !in_array($idMetodo, [2, 3], true) || $titular === '' || strlen($ultimos4) !== 4 || $tokenPago === '' || $fechaExpiracion === '') {
            throw new InvalidArgumentException('Datos de metodo de pago incompletos');
        }

        $query = "BEGIN SP_GUARDAR_METODO_PAGO(
                    :id_usuario,
                    :id_metodo,
                    :titular,
                    :ultimos_4,
                    :franquicia,
                    :token_pago,
                    TO_DATE(:fecha_expiracion, 'YYYY-MM-DD'),
                    :es_predeterminado,
                    :id_metodo_pago_usuario
                  ); END;";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        $idMetodoPagoUsuario = 0;
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_metodo', $idMetodo, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':titular', $titular);
        oci_bind_by_name($stmt, ':ultimos_4', $ultimos4);
        oci_bind_by_name($stmt, ':franquicia', $franquicia);
        oci_bind_by_name($stmt, ':token_pago', $tokenPago);
        oci_bind_by_name($stmt, ':fecha_expiracion', $fechaExpiracion);
        oci_bind_by_name($stmt, ':es_predeterminado', $esPredeterminado, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_metodo_pago_usuario', $idMetodoPagoUsuario, 64, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
        return (int) $idMetodoPagoUsuario;
    }

    public function eliminar(int $idMetodoPagoUsuario, int $idUsuario): bool {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return false;
        }

        $query = "UPDATE METODO_PAGO_USUARIO
                  SET ACTIVO = 0,
                      ES_PREDETERMINADO = 0
                  WHERE ID_METODO_PAGO_USUARIO = :id_metodo_pago_usuario
                    AND ID_USUARIO = :id_usuario
                    AND ACTIVO = 1";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_metodo_pago_usuario', $idMetodoPagoUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $affected = oci_num_rows($stmt) > 0;
        oci_free_statement($stmt);
        return $affected;
    }

    public function actualizar(int $idMetodoPagoUsuario, int $idUsuario, array $data): bool {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return false;
        }

        $titular = trim((string) ($data['titular'] ?? ''));
        $fechaExpiracion = trim((string) ($data['fecha_expiracion'] ?? ''));
        $esPredeterminado = !empty($data['es_predeterminado']) ? 1 : 0;

        if ($titular === '' || $fechaExpiracion === '') {
            throw new InvalidArgumentException('Completa titular y fecha de expiracion');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaExpiracion)) {
            throw new InvalidArgumentException('Fecha de expiracion invalida');
        }

        $query = "UPDATE METODO_PAGO_USUARIO
                  SET TITULAR = :titular,
                      FECHA_EXPIRACION = TO_DATE(:fecha_expiracion, 'YYYY-MM-DD'),
                      ES_PREDETERMINADO = :es_predeterminado
                  WHERE ID_METODO_PAGO_USUARIO = :id_metodo_pago_usuario
                    AND ID_USUARIO = :id_usuario
                    AND ACTIVO = 1";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':titular', $titular);
        oci_bind_by_name($stmt, ':fecha_expiracion', $fechaExpiracion);
        oci_bind_by_name($stmt, ':es_predeterminado', $esPredeterminado, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_metodo_pago_usuario', $idMetodoPagoUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $affected = oci_num_rows($stmt) > 0;
        oci_free_statement($stmt);
        return $affected;
    }

    public function establecerPredeterminado(int $idMetodoPagoUsuario, int $idUsuario): bool {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return false;
        }

        $query = "UPDATE METODO_PAGO_USUARIO
                  SET ES_PREDETERMINADO = 1
                  WHERE ID_METODO_PAGO_USUARIO = :id_metodo_pago_usuario
                    AND ID_USUARIO = :id_usuario
                    AND ACTIVO = 1";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_metodo_pago_usuario', $idMetodoPagoUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $affected = oci_num_rows($stmt) > 0;
        oci_free_statement($stmt);
        return $affected;
    }
}
