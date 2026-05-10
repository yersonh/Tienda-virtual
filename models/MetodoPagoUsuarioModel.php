<?php

class MetodoPagoUsuarioModel {

    private $conn;
    private ?array $columnasMetodoPagoUsuario = null;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    private function normalizarFila(array $row): array {

        $data = array_change_key_case($row, CASE_LOWER);

        $data['activo'] = (int) ($data['activo'] ?? 0);
        $data['es_predeterminado'] = (int) ($data['es_predeterminado'] ?? 0);
        $data['vencida'] = (int) ($data['vencida'] ?? 0);

        $data['id_usuario'] = (int) ($data['id_usuario'] ?? 0);
        $data['id_metodo'] = (int) ($data['id_metodo'] ?? 0);
        $data['id_metodo_pago_usuario'] = (int) ($data['id_metodo_pago_usuario'] ?? 0);

        return $data;
    }

    private function columnaExiste(string $columna): bool {
        if ($this->columnasMetodoPagoUsuario === null) {
            $query = "SELECT COLUMN_NAME
                      FROM USER_TAB_COLUMNS
                      WHERE TABLE_NAME = 'METODO_PAGO_USUARIO'";

            $stmt = @oci_parse($this->conn, $query);
            if (!$stmt || !@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                if ($stmt) {
                    error_log('MetodoPagoUsuarioModel columnas: ' . $this->oracleErrorMessage($stmt));
                    oci_free_statement($stmt);
                }
                $this->columnasMetodoPagoUsuario = [];
                return false;
            }

            $columnas = [];
            while ($row = oci_fetch_assoc($stmt)) {
                $columnas[strtoupper((string) $row['COLUMN_NAME'])] = true;
            }
            oci_free_statement($stmt);
            $this->columnasMetodoPagoUsuario = $columnas;
        }

        return isset($this->columnasMetodoPagoUsuario[strtoupper($columna)]);
    }

    private function fechaExpiracionSelect(): string {
        return "
            CASE
                WHEN REGEXP_LIKE(mpu.FECHA_EXPIRACION, '^(0[1-9]|1[0-2])/[0-9]{4}$')
                THEN mpu.FECHA_EXPIRACION
                ELSE NULL
            END
        ";
    }

    private function tarjetaVencidaSelect(): string {
        return "
            CASE
                WHEN REGEXP_LIKE(mpu.FECHA_EXPIRACION, '^(0[1-9]|1[0-2])/[0-9]{4}$')
                AND TO_DATE('01/' || mpu.FECHA_EXPIRACION, 'DD/MM/YYYY')
                    < TRUNC(SYSDATE, 'MM')
                THEN 1
                ELSE 0
            END
        ";
    }

    private function fechaCreacionSelect(): string {
        return $this->columnaExiste('FECHA_CREACION')
            ? "TO_CHAR(mpu.FECHA_CREACION, 'YYYY-MM-DD HH24:MI:SS')"
            : "''";
    }

    private function tokenWompiSelect(): string {
        return $this->columnaExiste('TOKEN_WOMPI')
            ? "mpu.TOKEN_WOMPI"
            : ($this->columnaExiste('TOKEN_PAGO') ? "mpu.TOKEN_PAGO" : "''");
    }

    private function fuenteWompiSelect(): string {
        return "
            CASE
                WHEN REGEXP_LIKE(TO_CHAR(mpu.ID_FUENTE_WOMPI), '^[0-9]+$')
                THEN mpu.ID_FUENTE_WOMPI
                ELSE NULL
            END
        ";
    }

    private function estadoWompiSelect(): string {
        return "
            CASE
                WHEN mpu.ESTADO_WOMPI IS NOT NULL
                THEN UPPER(TRIM(mpu.ESTADO_WOMPI))
                ELSE 'UNKNOWN'
            END
        ";
    }

    private function cacheKey(int $idUsuario, int $soloActivos): string {
        return $idUsuario . ':' . ($soloActivos === 1 ? 1 : 0);
    }

    private function obtenerCache(int $idUsuario, int $soloActivos): ?array {
        $this->ensureSession();
        $key = $this->cacheKey($idUsuario, $soloActivos);
        $cache = $_SESSION['metodos_pago_cache'][$key] ?? null;

        if (
            is_array($cache)
            && (int) ($cache['expires'] ?? 0) >= time()
            && (int) ($cache['id_usuario'] ?? 0) === $idUsuario
            && isset($cache['data'])
            && is_array($cache['data'])
        ) {
            return $cache['data'];
        }

        return null;
    }

    private function guardarCache(int $idUsuario, int $soloActivos, array $data): void {
        $this->ensureSession();
        $_SESSION['metodos_pago_cache'][$this->cacheKey($idUsuario, $soloActivos)] = [
            'expires' => time() + 45,
            'id_usuario' => $idUsuario,
            'data' => $data
        ];
    }

    private function limpiarCache(int $idUsuario): void {
        $this->ensureSession();
        unset(
            $_SESSION['metodos_pago_cache'][$this->cacheKey($idUsuario, 0)],
            $_SESSION['metodos_pago_cache'][$this->cacheKey($idUsuario, 1)]
        );
    }

    public function invalidarCacheUsuario(int $idUsuario): void {
        if ($idUsuario <= 0) {
            return;
        }

        $this->limpiarCache($idUsuario);
    }

    private function quitarPredeterminado(int $idUsuario): void {
        $query = "UPDATE METODO_PAGO_USUARIO
                  SET ES_PREDETERMINADO = 0
                  WHERE ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
    }

    public function desactivarVencidasUsuario(int $idUsuario): int {
        if ($idUsuario <= 0 || !$this->columnaExiste('FECHA_EXPIRACION')) {
            return 0;
        }

        $query = "UPDATE METODO_PAGO_USUARIO
                  SET ACTIVO = 0,
                      ES_PREDETERMINADO = 0
                  WHERE ID_USUARIO = :id_usuario
                    AND ID_METODO IN (2, 3)
                    AND ACTIVO = 1
                    AND LAST_DAY(FECHA_EXPIRACION) < TRUNC(SYSDATE)";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $affected = oci_num_rows($stmt);
        @oci_commit($this->conn);
        oci_free_statement($stmt);
        if ($affected > 0) {
            $this->limpiarCache($idUsuario);
        }

        return $affected;
    }

    public function obtenerPorUsuario(int $idUsuario, int $soloActivos = 1): array {
        if ($idUsuario <= 0) {
            return [];
        }

        $soloActivos = $soloActivos === 1 ? 1 : 0;
        $cache = $this->obtenerCache($idUsuario, $soloActivos);
        if ($cache !== null) {
            return $cache;
        }

        try {
            $desactivadas = $this->desactivarVencidasUsuario($idUsuario);
            if ($desactivadas > 0) {
                $this->ensureSession();
                $_SESSION['payment_expired_notice'] = 'Una o mas tarjetas vencidas fueron desactivadas automaticamente. Puedes eliminarlas desde tus tarjetas guardadas.';
            }
        } catch (Throwable $e) {
            error_log('MetodoPagoUsuarioModel desactivar vencidas: ' . $e->getMessage());
            @oci_rollback($this->conn);
        }

        $fechaExpiracionSelect = $this->fechaExpiracionSelect();
        $fechaCreacionSelect = $this->fechaCreacionSelect();
        $tarjetaVencidaSelect = $this->tarjetaVencidaSelect();
        $tokenWompiSelect = $this->tokenWompiSelect();
        $fuenteWompiSelect = $this->fuenteWompiSelect();
        $estadoWompiSelect = $this->estadoWompiSelect();
        $query = "SELECT mpu.ID_METODO_PAGO_USUARIO,
                         mpu.ID_USUARIO,
                         mpu.ID_METODO,
                         CASE mpu.ID_METODO
                             WHEN 2 THEN 'Tarjeta debito'
                             WHEN 3 THEN 'Tarjeta credito'
                             WHEN 5 THEN 'Wompi'
                             ELSE 'Tarjeta'
                         END AS FORMA_PAGO,
                         mpu.TITULAR,
                         mpu.ULTIMOS_4,
                         mpu.FRANQUICIA,
                         {$tokenWompiSelect} AS TOKEN_WOMPI,
                         {$fuenteWompiSelect} AS ID_FUENTE_WOMPI,
                         {$estadoWompiSelect} AS ESTADO_WOMPI,
                         {$fechaExpiracionSelect} AS FECHA_EXPIRACION_TEXTO,
                         {$tarjetaVencidaSelect} AS VENCIDA,
                         mpu.ES_PREDETERMINADO,
                         mpu.ACTIVO,
                         {$fechaCreacionSelect} AS FECHA_CREACION
                  FROM METODO_PAGO_USUARIO mpu
                  WHERE mpu.ID_USUARIO = :id_usuario
                    AND mpu.ID_METODO IN (2, 3)";

        if ($soloActivos === 1) {
            $query .= " AND mpu.ACTIVO = 1";
        }

        $query .= " ORDER BY mpu.ACTIVO DESC, mpu.ES_PREDETERMINADO DESC, mpu.ID_METODO_PAGO_USUARIO DESC";

        $stmt = @oci_parse($this->conn, $query);
        if (!$stmt) {
            error_log('MetodoPagoUsuarioModel obtenerPorUsuario parse: ' . $this->oracleErrorMessage());
            return [];
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            error_log('MetodoPagoUsuarioModel obtenerPorUsuario execute: ' . $this->oracleErrorMessage($stmt));
            oci_free_statement($stmt);
            return [];
        }

        $metodos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $metodos[] = $this->normalizarFila($row);
        }

        oci_free_statement($stmt);
        $this->guardarCache($idUsuario, $soloActivos, $metodos);
        return $metodos;
    }

    public function obtenerPredeterminado(int $idUsuario): ?array {
        if ($idUsuario <= 0) {
            return null;
        }

        foreach ($this->obtenerPorUsuario($idUsuario, 1) as $metodo) {
            if ((int) ($metodo['es_predeterminado'] ?? 0) === 1) {
                return $metodo;
            }
        }

        return null;
    }

    public function obtenerPorIdUsuario(int $idMetodoPagoUsuario, int $idUsuario): ?array {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return null;
        }

        try {
            $this->desactivarVencidasUsuario($idUsuario);
        } catch (Throwable $e) {
            error_log('MetodoPagoUsuarioModel desactivar vencida por id: ' . $e->getMessage());
        }

        $metodosCache = $this->obtenerCache($idUsuario, 0) ?? $this->obtenerCache($idUsuario, 1);
        if (is_array($metodosCache)) {
            foreach ($metodosCache as $metodo) {
                if (
                    (int) ($metodo['id_metodo_pago_usuario'] ?? 0) === $idMetodoPagoUsuario
                    && (int) ($metodo['activo'] ?? 0) === 1
                ) {
                    return $metodo;
                }
            }
        }

        foreach ($this->obtenerPorUsuario($idUsuario, 1) as $metodo) {
            if ((int) ($metodo['id_metodo_pago_usuario'] ?? 0) === $idMetodoPagoUsuario) {
                return $metodo;
            }
        }

        $fechaExpiracionSelect = $this->fechaExpiracionSelect();
        $fechaCreacionSelect = $this->fechaCreacionSelect();
        $tarjetaVencidaSelect = $this->tarjetaVencidaSelect();
        $tokenWompiSelect = $this->tokenWompiSelect();
        $fuenteWompiSelect = $this->fuenteWompiSelect();
        $estadoWompiSelect = $this->estadoWompiSelect();
        
        $query = "SELECT mpu.ID_METODO_PAGO_USUARIO,
                         mpu.ID_USUARIO,
                         mpu.ID_METODO,
                         CASE mpu.ID_METODO
                             WHEN 2 THEN 'Tarjeta debito'
                             WHEN 3 THEN 'Tarjeta credito'
                             ELSE 'Tarjeta'
                         END AS FORMA_PAGO,
                         mpu.TITULAR,
                         mpu.ULTIMOS_4,
                         mpu.FRANQUICIA,
                         {$tokenWompiSelect} AS TOKEN_WOMPI,
                         {$fuenteWompiSelect} AS ID_FUENTE_WOMPI,
                         {$estadoWompiSelect} AS ESTADO_WOMPI,
                         {$fechaExpiracionSelect} AS FECHA_EXPIRACION_TEXTO,
                         {$tarjetaVencidaSelect} AS VENCIDA,
                         mpu.ES_PREDETERMINADO,
                         mpu.ACTIVO,
                         {$fechaCreacionSelect} AS FECHA_CREACION
                  FROM METODO_PAGO_USUARIO mpu
                  WHERE mpu.ID_METODO_PAGO_USUARIO = :id_metodo_pago_usuario
                    AND mpu.ID_USUARIO = :id_usuario
                    AND mpu.ACTIVO = 1
                    AND mpu.ID_METODO IN (2, 3, 5)
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
        $tokenWompi = trim((string) ($data['token_wompi'] ?? $data['token_pago'] ?? ''));
        $idFuenteWompi = (int) ($data['id_fuente_wompi'] ?? 0);
        $estadoWompi = strtoupper(trim((string) ($data['estado_wompi'] ?? 'AVAILABLE')));
        $fechaExpiracion = trim((string) ($data['fecha_expiracion'] ?? ''));
        $esPredeterminado = !empty($data['es_predeterminado']) ? 1 : 0;

        if ($idUsuario <= 0 || !in_array($idMetodo, [2, 3], true) || $titular === '' || strlen($ultimos4) !== 4 || $tokenWompi === '' || $idFuenteWompi <= 0 || $fechaExpiracion === '') {
            throw new InvalidArgumentException('Datos de metodo de pago incompletos');
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{4}$/', $fechaExpiracion)) {
            throw new InvalidArgumentException('Ingresa la expiracion en formato MM/YYYY');
        }
        $expParts = explode('/', $fechaExpiracion);
        if ((int)$expParts[1] < (int)date('Y') || ((int)$expParts[1] === (int)date('Y') && (int)$expParts[0] < (int)date('m'))) {
            throw new InvalidArgumentException('La tarjeta ingresada se encuentra vencida');
        }
        if (!in_array($franquicia, ['VISA', 'MASTERCARD'], true)) {
            throw new InvalidArgumentException('Solo se permiten tarjetas VISA o MASTERCARD tokenizadas');
        }
        if ($this->existeDuplicadoActivo($idUsuario, $franquicia, $ultimos4, $fechaExpiracion)) {
            throw new InvalidArgumentException('Esta tarjeta ya esta guardada para tu usuario');
        }

        if ($esPredeterminado === 1) {
            $this->quitarPredeterminado($idUsuario);
        }
    
        $query = "BEGIN SP_GUARDAR_METODO_PAGO(
            :id_usuario,
            :id_metodo,
            :titular,
            :ultimos_4,
            :franquicia,
            :token,
            :fecha_expiracion,
            :es_predeterminado,
            :tipo_token,
            :email_wompi,
            :id_fuente_wompi,
            :estado_wompi
        ); END;";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        $tipoToken = 'CARD';
        $emailWompi = '';

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_metodo', $idMetodo, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':titular', $titular);
        oci_bind_by_name($stmt, ':ultimos_4', $ultimos4);
        oci_bind_by_name($stmt, ':franquicia', $franquicia);
        oci_bind_by_name($stmt, ':token', $tokenWompi);
        oci_bind_by_name($stmt, ':fecha_expiracion', $fechaExpiracion);
        oci_bind_by_name($stmt, ':es_predeterminado', $esPredeterminado, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':tipo_token', $tipoToken);
        oci_bind_by_name($stmt, ':email_wompi', $emailWompi);
        oci_bind_by_name($stmt, ':id_fuente_wompi', $idFuenteWompi, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':estado_wompi', $estadoWompi);

        error_log("=== LOG BIND NUMBER METODO PAGO ===");
        error_log("valor: " . var_export($idUsuario, true) . " | tipo: " . gettype($idUsuario) . " | destino: ID_USUARIO");
        error_log("valor: " . var_export($idMetodo, true) . " | tipo: " . gettype($idMetodo) . " | destino: ID_METODO");
        error_log("valor: " . var_export($idFuenteWompi, true) . " | tipo: " . gettype($idFuenteWompi) . " | destino: ID_FUENTE_WOMPI");
        error_log("valor: " . var_export($esPredeterminado, true) . " | tipo: " . gettype($esPredeterminado) . " | destino: ES_PREDETERMINADO");

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
        $idMetodoPagoUsuario = $this->obtenerIdPorToken($tokenWompi, $idUsuario);
        if ($esPredeterminado === 1 && $idMetodoPagoUsuario > 0) {
            $this->establecerPredeterminado($idMetodoPagoUsuario, $idUsuario);
        }
        $this->limpiarCache($idUsuario);

        return $idMetodoPagoUsuario;
    }

    private function existeDuplicadoActivo(int $idUsuario, string $franquicia, string $ultimos4, string $fechaExpiracion): bool {
        $query = "SELECT COUNT(*) AS TOTAL
                  FROM METODO_PAGO_USUARIO
                  WHERE ID_USUARIO = :id_usuario
                    AND ACTIVO = 1
                    AND UPPER(FRANQUICIA) = :franquicia
                    AND ULTIMOS_4 = :ultimos_4
                    AND TO_CHAR(FECHA_EXPIRACION, 'MM/YYYY') = :fecha_expiracion";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':franquicia', $franquicia);
        oci_bind_by_name($stmt, ':ultimos_4', $ultimos4);
        oci_bind_by_name($stmt, ':fecha_expiracion', $fechaExpiracion);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return ((int) ($row['TOTAL'] ?? 0)) > 0;
    }

    private function obtenerIdPorToken(string $tokenPago, int $idUsuario): int {
        $tokenColumn = $this->columnaExiste('TOKEN_WOMPI') ? 'TOKEN_WOMPI' : 'TOKEN_PAGO';
        $query = "SELECT ID_METODO_PAGO_USUARIO
                  FROM METODO_PAGO_USUARIO
                  WHERE {$tokenColumn} = :token_pago
                    AND ID_USUARIO = :id_usuario
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':token_pago', $tokenPago);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['ID_METODO_PAGO_USUARIO'] ?? 0);
    }

    public function eliminar(int $idMetodoPagoUsuario, int $idUsuario): bool {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return false;
        }

        $query = "BEGIN SP_ELIMINAR_METODO_PAGO(:id_metodo_pago_usuario, :id_usuario); END;";

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

        oci_free_statement($stmt);
        $this->limpiarCache($idUsuario);
        return true;
    }

    public function cambiarActivo(int $idMetodoPagoUsuario, int $idUsuario, int $activo): bool {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return false;
        }

        $activoInt = $activo === 1 ? 1 : 0;
        $query = "UPDATE METODO_PAGO_USUARIO
                  SET ACTIVO = :activo,
                      ES_PREDETERMINADO = CASE WHEN :activo_pred = 0 THEN 0 ELSE ES_PREDETERMINADO END
                  WHERE ID_METODO_PAGO_USUARIO = :id_metodo_pago_usuario
                    AND ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':activo', $activoInt, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':activo_pred', $activoInt, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_metodo_pago_usuario', $idMetodoPagoUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $affected = oci_num_rows($stmt) > 0;
        oci_free_statement($stmt);
        if ($affected) {
            $this->limpiarCache($idUsuario);
        }
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

        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{4})$/', $fechaExpiracion, $matches)) {
            $fechaExpiracion = $matches[1] . '/' . $matches[2];
        } elseif (preg_match('/^(\d{4})-(\d{2})(-\d{2})?$/', $fechaExpiracion, $matches)) {
            $fechaExpiracion = $matches[2] . '/' . $matches[1];
        }

        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{4}$/', $fechaExpiracion)) {
            throw new InvalidArgumentException('Ingresa la expiracion en formato MM/YYYY');
        }
        $expParts = explode('/', $fechaExpiracion);
        if ((int)$expParts[1] < (int)date('Y') || ((int)$expParts[1] === (int)date('Y') && (int)$expParts[0] < (int)date('m'))) {
            throw new InvalidArgumentException('La tarjeta ingresada se encuentra vencida');
        }

        $query = "BEGIN SP_ACTUALIZAR_METODO_PAGO(
                    :id_metodo_pago_usuario,
                    :id_usuario,
                    :titular,
                    :fecha_expiracion,
                    :es_predeterminado
                  ); END;";

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

        oci_free_statement($stmt);
        $this->limpiarCache($idUsuario);
        return true;
    }

    public function establecerPredeterminado(int $idMetodoPagoUsuario, int $idUsuario): bool {
        if ($idMetodoPagoUsuario <= 0 || $idUsuario <= 0) {
            return false;
        }

        $this->desactivarVencidasUsuario($idUsuario);

        $query = "BEGIN SP_PREDETERMINAR_METODO_PAGO(:id_metodo_pago_usuario, :id_usuario); END;";

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

        oci_free_statement($stmt);

        $this->limpiarCache($idUsuario);
        return true;
    }
}

