<?php
require_once __DIR__ . '/../config/database.php';

class DevolucionModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

    private function parse(string $sql) {
        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            $e = oci_error($this->conn);
            throw new Exception($e['message'] ?? 'Error Oracle al preparar consulta');
        }
        return $stmt;
    }

    private function exec($stmt, int $mode = OCI_NO_AUTO_COMMIT): void {
        if (!@oci_execute($stmt, $mode)) {
            $e = oci_error($stmt);
            throw new Exception($e['message'] ?? 'Error Oracle al ejecutar consulta');
        }
    }

    private function normalizeRow(array $row): array {
        $out = [];
        foreach ($row as $key => $value) {
            if ($value instanceof OCILob) {
                $loaded = $value->load();
                $value  = ($loaded === false) ? '' : (string) $loaded;
            }
            $out[strtolower($key)] = $value;
        }
        return $out;
    }

    private function rows($stmt): array {
        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $this->normalizeRow($row);
        }
        return $rows;
    }

    // ─── USUARIO: LECTURA ─────────────────────────────────────────────────────

    public function obtenerItemsPedidoParaDevolucion(int $idPedido, int $idUsuario): array {
        $sql = "SELECT dv.ID_DETALLE,
                       dv.ID_PRODUCTO,
                       dv.ID_REFERENCIA,
                       dv.CANTIDAD,
                       dv.PRECIO_UNITARIO,
                       p.NOMBRE  AS PRODUCTO_NOMBRE,
                       rp.NUMERO_REFERENCIA,
                       (SELECT pi.URL
                          FROM PRODUCTO_IMAGEN pi
                         WHERE pi.ID_PRODUCTO = dv.ID_PRODUCTO
                         ORDER BY pi.ORDEN
                         FETCH FIRST 1 ROWS ONLY) AS IMAGEN
                  FROM DETALLE_VENTA dv
                 INNER JOIN PEDIDO pe ON pe.ID_VENTA = dv.ID_VENTA
                 INNER JOIN VENTA   v  ON v.ID_VENTA  = dv.ID_VENTA
                 INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = dv.ID_PRODUCTO
                  LEFT JOIN REFERENCIA_PRODUCTO rp ON rp.ID_REFERENCIA = dv.ID_REFERENCIA
                 WHERE pe.ID_PEDIDO = :id_pedido
                   AND v.ID_USUARIO = :id_usuario
                   AND pe.ID_ESTADO = 4
                 ORDER BY dv.ID_DETALLE";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_pedido',  $idPedido,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        $this->exec($stmt);
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function obtenerPedidoParaDevolucion(int $idPedido, int $idUsuario): ?array {
        $sql = "SELECT pe.ID_PEDIDO,
                       pe.ID_ESTADO,
                       TO_CHAR(pe.CREATED_AT, 'DD/MM/YYYY') AS FECHA_PEDIDO,
                       NVL(v.TOTAL, 0) AS TOTAL
                  FROM PEDIDO pe
                 INNER JOIN VENTA v ON v.ID_VENTA = pe.ID_VENTA
                 WHERE pe.ID_PEDIDO = :id_pedido
                   AND v.ID_USUARIO = :id_usuario
                   AND pe.ID_ESTADO = 4
                 FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_pedido',  $idPedido,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        $this->exec($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function obtenerDevolucionesPorUsuario(int $idUsuario, ?int $idEstado = null): array {
        $sql = "SELECT d.ID_DEVOLUCION, d.ID_PEDIDO, d.ID_ESTADO_DEVOLUCION,
                       TO_CHAR(d.FECHA_SOLICITUD, 'DD/MM/YYYY') AS FECHA_SOLICITUD,
                       d.REEMBOLSO_ID_WOMPI, d.REEMBOLSO_ESTADO,
                       ed.NOMBRE AS ESTADO_NOMBRE
                FROM DEVOLUCION d
                JOIN ESTADO_DEVOLUCION ed ON ed.ID_ESTADO_DEVOLUCION = d.ID_ESTADO_DEVOLUCION
                WHERE d.ID_USUARIO = :id_usuario";

        if ($idEstado !== null) {
            $sql .= " AND d.ID_ESTADO_DEVOLUCION = :id_estado";
        }
        $sql .= " ORDER BY d.FECHA_SOLICITUD DESC, d.ID_DEVOLUCION DESC";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if ($idEstado !== null) {
            oci_bind_by_name($stmt, ':id_estado', $idEstado, -1, SQLT_INT);
        }
        $this->exec($stmt);
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function obtenerDevolucionPorId(int $idDevolucion, ?int $idUsuario = null): ?array {
        $sql = "SELECT d.ID_DEVOLUCION,
                       d.ID_PEDIDO,
                       d.ID_USUARIO,
                       d.ID_ESTADO_DEVOLUCION,
                       TO_CHAR(d.FECHA_SOLICITUD,  'DD/MM/YYYY HH24:MI') AS FECHA_SOLICITUD,
                       TO_CHAR(d.FECHA_APROBACION, 'DD/MM/YYYY HH24:MI') AS FECHA_APROBACION,
                       TO_CHAR(d.FECHA_RECEPCION,  'DD/MM/YYYY HH24:MI') AS FECHA_RECEPCION,
                       TO_CHAR(d.FECHA_REEMBOLSO,  'DD/MM/YYYY HH24:MI') AS FECHA_REEMBOLSO,
                       d.OBSERVACION_CLIENTE,
                       d.OBSERVACION_ADMIN,
                       NVL(d.REEMBOLSO_REALIZADO, 0) AS REEMBOLSO_REALIZADO,
                       d.REEMBOLSO_ESTADO,
                       d.REEMBOLSO_ID_WOMPI,
                       d.REEMBOLSO_ERROR,
                       ed.NOMBRE AS ESTADO_NOMBRE
                  FROM DEVOLUCION d
                 INNER JOIN ESTADO_DEVOLUCION ed ON ed.ID_ESTADO_DEVOLUCION = d.ID_ESTADO_DEVOLUCION
                 WHERE d.ID_DEVOLUCION = :id_devolucion";

        if ($idUsuario !== null) {
            $sql .= " AND d.ID_USUARIO = :id_usuario";
        }
        $sql .= " FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_devolucion', $idDevolucion, -1, SQLT_INT);
        if ($idUsuario !== null) {
            oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        }
        $this->exec($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return null;
        }

        $devolucion = $this->normalizeRow($row);
        $devolucion['detalles'] = $this->obtenerDetallesDevolucion($idDevolucion);

        // Monto estimado = precio_unitario × cantidad_aprobada por cada detalle con aprobación
        $devolucion['monto_reembolso_estimado'] = (float) array_sum(array_map(
            fn(array $d) => (float) ($d['precio_unitario'] ?? 0) * max(1, (int) ($d['cantidad_aprobada'] ?? 0)),
            array_filter($devolucion['detalles'], fn($d) => (int) ($d['cantidad_aprobada'] ?? 0) > 0)
        ));

        return $devolucion;
    }

    private function obtenerDetallesDevolucion(int $idDevolucion): array {
        $sql = "SELECT dd.ID_DEVOLUCION_DETALLE,
                       dd.ID_DEVOLUCION,
                       dd.ID_DETALLE,
                       dd.ID_PRODUCTO,
                       dd.ID_REFERENCIA,
                       dd.CANTIDAD,
                       NVL(dd.CANTIDAD_APROBADA, 0) AS CANTIDAD_APROBADA,
                       dd.MOTIVO,
                       dd.COMENTARIO,
                       dd.ESTADO,
                       NVL(dd.PRODUCTO_RECIBIDO, 0) AS PRODUCTO_RECIBIDO,
                       p.NOMBRE  AS PRODUCTO_NOMBRE,
                       rp.NUMERO_REFERENCIA,
                       NVL(dv_p.PRECIO_UNITARIO, 0) AS PRECIO_UNITARIO
                  FROM DEVOLUCION_DETALLE dd
                 INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = dd.ID_PRODUCTO
                  LEFT JOIN REFERENCIA_PRODUCTO rp ON rp.ID_REFERENCIA = dd.ID_REFERENCIA
                  LEFT JOIN DETALLE_VENTA dv_p ON dv_p.ID_DETALLE = dd.ID_DETALLE
                 WHERE dd.ID_DEVOLUCION = :id_devolucion
                 ORDER BY dd.ID_DEVOLUCION_DETALLE";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_devolucion', $idDevolucion, -1, SQLT_INT);
        $this->exec($stmt);
        $detalles = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $detalle = $this->normalizeRow($row);
            $detalle['imagenes'] = $this->obtenerImagenesDetalle((int) $detalle['id_devolucion_detalle']);
            $detalles[] = $detalle;
        }
        oci_free_statement($stmt);
        return $detalles;
    }

    private function obtenerImagenesDetalle(int $idDevolucionDetalle): array {
        $sql = "SELECT ID_IMAGEN, RUTA_IMAGEN, TIPO_USUARIO, TO_CHAR(FECHA_SUBIDA,'DD/MM/YYYY') AS FECHA_SUBIDA
                  FROM DEVOLUCION_IMAGEN
                 WHERE ID_DEVOLUCION_DETALLE = :id_det
                 ORDER BY ID_IMAGEN";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_det', $idDevolucionDetalle, -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt);
            error_log("DevolucionModel::obtenerImagenesDetalle (ID Detalle: $idDevolucionDetalle) - " . ($e['message'] ?? 'Error desconocido'));
            oci_free_statement($stmt);
            return [];
        }
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function existeDevolucionActivaPorPedido(int $idPedido, int $idUsuario): bool {
        $sql = "SELECT COUNT(*) AS TOTAL
                  FROM DEVOLUCION
                 WHERE ID_PEDIDO              = :id_pedido
                   AND ID_USUARIO             = :id_usuario
                   AND ID_ESTADO_DEVOLUCION NOT IN (
                       SELECT ID_ESTADO_DEVOLUCION FROM ESTADO_DEVOLUCION
                        WHERE UPPER(NOMBRE) LIKE '%RECHAZ%'
                   )
                 FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_pedido',  $idPedido,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        $this->exec($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return ((int) ($row['TOTAL'] ?? 0)) > 0;
    }

    // ─── USUARIO: ESCRITURA ───────────────────────────────────────────────────

    public function crearDevolucion(int $idPedido, int $idUsuario): int {
        $outId = -1;
        $sql = "INSERT INTO DEVOLUCION
                       (ID_DEVOLUCION, ID_PEDIDO, ID_USUARIO, ID_ESTADO_DEVOLUCION, FECHA_SOLICITUD)
                VALUES (SEQ_DEVOLUCION.NEXTVAL, :id_pedido, :id_usuario, 1, SYSDATE)
                RETURNING ID_DEVOLUCION INTO :out_id";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_pedido',  $idPedido,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':out_id',     $outId,     20, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
        return (int) $outId;
    }

    public function crearDevolucionDetalle(
        int    $idDevolucion,
        int    $idDetalle,
        int    $idProducto,
        int    $idReferencia,
        int    $cantidad,
        string $motivo,
        string $comentario
    ): int {
        $outId = -1;
        $sql = "INSERT INTO DEVOLUCION_DETALLE
                       (ID_DEVOLUCION_DETALLE, ID_DEVOLUCION, ID_DETALLE,
                        ID_PRODUCTO, ID_REFERENCIA, CANTIDAD,
                        MOTIVO, COMENTARIO, ESTADO, PRODUCTO_RECIBIDO)
                VALUES (SEQ_DEVOLUCION_DETALLE.NEXTVAL, :id_devolucion, :id_detalle,
                        :id_producto, NULLIF(:id_referencia, 0), :cantidad,
                        :motivo, :comentario, 'PENDIENTE', 0)
                RETURNING ID_DEVOLUCION_DETALLE INTO :out_id";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_devolucion',  $idDevolucion,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_detalle',     $idDetalle,     -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_producto',    $idProducto,    -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_referencia',  $idReferencia,  -1, SQLT_INT);
        oci_bind_by_name($stmt, ':cantidad',        $cantidad,      -1, SQLT_INT);
        oci_bind_by_name($stmt, ':motivo',          $motivo);
        oci_bind_by_name($stmt, ':comentario',      $comentario);
        oci_bind_by_name($stmt, ':out_id',          $outId,        20, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
        return (int) $outId;
    }

    public function guardarImagenDevolucion(int $idDevolucionDetalle, string $rutaRelativa, string $tipoUsuario = 'CLIENTE'): void {
        $sql = "INSERT INTO DEVOLUCION_IMAGEN
                       (ID_IMAGEN, ID_DEVOLUCION_DETALLE, RUTA_IMAGEN, TIPO_USUARIO, FECHA_SUBIDA)
                VALUES (SEQ_DEVOLUCION_IMAGEN.NEXTVAL, :id_det, :ruta, :tipo, SYSDATE)";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_det', $idDevolucionDetalle, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ruta',   $rutaRelativa);
        oci_bind_by_name($stmt, ':tipo',   $tipoUsuario);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    // ─── ADMIN: LECTURA ───────────────────────────────────────────────────────

    public function obtenerDevolucionesAdmin(?int $idEstado = null): array {
        $where = $idEstado !== null ? " WHERE d.ID_ESTADO_DEVOLUCION = :id_estado" : "";

        $sql = "SELECT d.ID_DEVOLUCION,
                       d.ID_PEDIDO,
                       d.ID_USUARIO,
                       d.ID_ESTADO_DEVOLUCION,
                       TO_CHAR(d.FECHA_SOLICITUD, 'DD/MM/YYYY') AS FECHA_SOLICITUD,
                       ed.NOMBRE                                 AS ESTADO_NOMBRE,
                       NVL(
                           TRIM(NVL(per.NOMBRES,'') || ' ' || NVL(per.APELLIDOS,'')),
                           u.USERNAME
                       )                                         AS CLIENTE,
                       u.USERNAME,
                       NVL(d.REEMBOLSO_REALIZADO, 0)             AS REEMBOLSO_REALIZADO,
                       d.REEMBOLSO_ESTADO,
                       d.REEMBOLSO_ID_WOMPI
                  FROM DEVOLUCION d
                 INNER JOIN ESTADO_DEVOLUCION ed ON ed.ID_ESTADO_DEVOLUCION = d.ID_ESTADO_DEVOLUCION
                 INNER JOIN USUARIO u            ON u.ID_USUARIO            = d.ID_USUARIO
                 INNER JOIN PERSONA per          ON per.ID_PERSONA          = u.ID_PERSONA"
               . $where
               . " ORDER BY d.FECHA_SOLICITUD DESC";

        $stmt = $this->parse($sql);
        if ($idEstado !== null) {
            oci_bind_by_name($stmt, ':id_estado', $idEstado, -1, SQLT_INT);
        }
        $this->exec($stmt);
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function obtenerEstados(): array {
        $stmt = $this->parse("SELECT ID_ESTADO_DEVOLUCION, NOMBRE FROM ESTADO_DEVOLUCION ORDER BY ID_ESTADO_DEVOLUCION");
        $this->exec($stmt);
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function contarPendientesAdmin(): int {
        $sql = "SELECT COUNT(*) AS TOTAL FROM DEVOLUCION WHERE ID_ESTADO_DEVOLUCION = 1";
        $stmt = $this->parse($sql);
        $this->exec($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int) ($row['TOTAL'] ?? 0);
    }

    // ─── ADMIN: ESCRITURA ─────────────────────────────────────────────────────

    public function actualizarCantidadAprobada(int $idDevolucionDetalle, int $cantidadAprobada): void {
        $sql = "UPDATE DEVOLUCION_DETALLE
                   SET CANTIDAD_APROBADA = :cantidad
                 WHERE ID_DEVOLUCION_DETALLE = :id_det";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':cantidad', $cantidadAprobada, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_det',   $idDevolucionDetalle, -1, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    /**
     * @param array $cantidadesAprobadas  [id_devolucion_detalle => cantidad_aprobada, ...]
     */
    public function aprobarDevolucion(int $idDevolucion, array $cantidadesAprobadas, string $observacionAdmin): void {
        foreach ($cantidadesAprobadas as $idDet => $qty) {
            $this->actualizarCantidadAprobada((int) $idDet, (int) $qty);
        }

        $stmt = $this->parse("BEGIN SP_APROBAR_DEVOLUCION(:p_id_devolucion, :p_observacion); END;");
        oci_bind_by_name($stmt, ':p_id_devolucion', $idDevolucion,      -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_observacion',   $observacionAdmin);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    public function rechazarDevolucion(int $idDevolucion, string $motivoAdmin): void {
        $stmt = $this->parse("BEGIN SP_RECHAZAR_DEVOLUCION(:p_id_devolucion, :p_motivo); END;");
        oci_bind_by_name($stmt, ':p_id_devolucion', $idDevolucion, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_motivo',        $motivoAdmin);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    public function marcarProductoRecibido(int $idDevolucionDetalle): void {
        $stmt = $this->parse("BEGIN SP_PRODUCTO_RECIBIDO(:p_id_devolucion_detalle); END;");
        oci_bind_by_name($stmt, ':p_id_devolucion_detalle', $idDevolucionDetalle, -1, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    /**
     * Registra el resultado del reembolso en DEVOLUCION.
     * @param string $estado  'REALIZADO' | 'PENDIENTE-MANUAL' | 'SIN-TRANSACCION'
     */
    public function registrarReembolso(
        int    $idDevolucion,
        string $estado,
        string $wompiId = '',
        string $error   = ''
    ): void {
        $realizado   = ($estado === 'REALIZADO') ? 1 : 0;
        $fechaClause = $realizado ? ', FECHA_REEMBOLSO = SYSDATE' : '';

        $sql = "UPDATE DEVOLUCION
                   SET REEMBOLSO_REALIZADO = :realizado,
                       REEMBOLSO_ESTADO    = :estado,
                       REEMBOLSO_ID_WOMPI  = :wompi_id,
                       REEMBOLSO_ERROR     = :error"
               . $fechaClause
               . " WHERE ID_DEVOLUCION = :id_devolucion";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':realizado',     $realizado,    -1, SQLT_INT);
        oci_bind_by_name($stmt, ':estado',        $estado);
        oci_bind_by_name($stmt, ':wompi_id',      $wompiId);
        oci_bind_by_name($stmt, ':error',         $error);
        oci_bind_by_name($stmt, ':id_devolucion', $idDevolucion, -1, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    // ─── WOMPI ────────────────────────────────────────────────────────────────

    public function obtenerTransaccionWompiPorPedido(int $idPedido): ?array {
        $sql = "SELECT pa.ID_TRANSACCION_WOMPI,
                       pa.REFERENCIA_WOMPI,
                       NVL(v.TOTAL, 0) AS TOTAL
                  FROM PAGO   pa
                 INNER JOIN PEDIDO  pe ON pe.ID_VENTA = pa.ID_VENTA
                 INNER JOIN VENTA   v  ON v.ID_VENTA  = pa.ID_VENTA
                 WHERE pe.ID_PEDIDO = :id_pedido
                   AND UPPER(TRIM(pa.ESTADO)) = 'APPROVED'
                 FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmt);
            error_log("DevolucionModel::obtenerTransaccionWompiPorPedido (Pedido: $idPedido) - " . ($e['message'] ?? 'Error desconocido'));
            oci_free_statement($stmt);
            return null;
        }
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return $row ? $this->normalizeRow($row) : null;
    }

    public function obtenerPrecioUnitarioDetalle(int $idDetalle): float {
        $sql = "SELECT NVL(PRECIO_UNITARIO, 0) AS PRECIO_UNITARIO
                  FROM DETALLE_VENTA
                 WHERE ID_DETALLE = :id_detalle
                 FETCH FIRST 1 ROWS ONLY";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_detalle', $idDetalle, -1, SQLT_INT);
        $this->exec($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (float) ($row['PRECIO_UNITARIO'] ?? 0);
    }

    // ─── INVENTARIO REACONDICIONADO ───────────────────────────────────────────

    public function obtenerInventarioReacondicionado(?string $estado = null): array {
        $sql = "SELECT ir.ID_ITEM_REACONDICIONADO,
                       ir.ID_PRODUCTO,
                       ir.ID_REFERENCIA,
                       ir.ID_DEVOLUCION_DETALLE,
                       ir.NUMERO_DEVOLUCIONES,
                       NVL(ir.DESCUENTO, 0)       AS DESCUENTO,
                       NVL(ir.PRECIO_ORIGINAL, 0)  AS PRECIO_ORIGINAL,
                       NVL(ir.PRECIO_FINAL, 0)     AS PRECIO_FINAL,
                       ir.ESTADO,
                       NVL(ir.DISPONIBLE, 0)       AS DISPONIBLE,
                       TO_CHAR(ir.FECHA_INGRESO, 'DD/MM/YYYY') AS FECHA_INGRESO,
                       p.NOMBRE AS PRODUCTO,
                       rp.NUMERO_REFERENCIA AS NOMBRE_REFERENCIA,
                       (SELECT pi.URL FROM PRODUCTO_IMAGEN pi
                         WHERE pi.ID_PRODUCTO = ir.ID_PRODUCTO
                         ORDER BY pi.ORDEN
                         FETCH FIRST 1 ROWS ONLY) AS IMAGEN
                  FROM INVENTARIO_REACONDICIONADO ir
                 INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = ir.ID_PRODUCTO
                  LEFT JOIN REFERENCIA_PRODUCTO rp ON rp.ID_REFERENCIA = ir.ID_REFERENCIA";

        if ($estado !== null) {
            $sql .= " WHERE ir.ESTADO = :estado";
        }
        $sql .= " ORDER BY ir.FECHA_INGRESO DESC";

        $stmt = $this->parse($sql);
        if ($estado !== null) {
            oci_bind_by_name($stmt, ':estado', $estado);
        }
        $this->exec($stmt);
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function obtenerOfertasReacondicionadas(): array {
        $stmt = $this->parse(
            "SELECT * FROM V_OFERTAS_REACONDICIONADOS ORDER BY NUMERO_DEVOLUCIONES ASC, PRECIO_FINAL ASC"
        );
        $this->exec($stmt);
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function obtenerStockMuerto(): array {
        $stmt = $this->parse(
            "SELECT * FROM V_STOCK_MUERTO ORDER BY FECHA_INGRESO DESC"
        );
        $this->exec($stmt);
        $result = $this->rows($stmt);
        oci_free_statement($stmt);
        return $result;
    }

    public function ofertarReacondicionado(int $idItem): void {
        $sql = "UPDATE INVENTARIO_REACONDICIONADO
                   SET ESTADO       = 'OFERTA_10',
                       DESCUENTO    = 10,
                       DISPONIBLE   = 1,
                       PRECIO_FINAL = PRECIO_ORIGINAL * 0.90
                 WHERE ID_ITEM_REACONDICIONADO = :id_item
                   AND NUMERO_DEVOLUCIONES     = 1";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_item', $idItem, -1, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    public function reofertarReacondicionado(int $idItem): void {
        $sql = "UPDATE INVENTARIO_REACONDICIONADO
                   SET ESTADO       = 'OFERTA_15',
                       DESCUENTO    = 15,
                       DISPONIBLE   = 1,
                       PRECIO_FINAL = PRECIO_ORIGINAL * 0.85
                 WHERE ID_ITEM_REACONDICIONADO = :id_item";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_item', $idItem, -1, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }

    public function marcarStockMuerto(int $idItem): void {
        $sql = "UPDATE INVENTARIO_REACONDICIONADO
                   SET ESTADO     = 'STOCK_MUERTO',
                       DISPONIBLE = 0
                 WHERE ID_ITEM_REACONDICIONADO = :id_item";

        $stmt = $this->parse($sql);
        oci_bind_by_name($stmt, ':id_item', $idItem, -1, SQLT_INT);
        $this->exec($stmt);
        oci_free_statement($stmt);
    }
}
