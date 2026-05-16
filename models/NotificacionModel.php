<?php
// models/NotificacionModel.php

class NotificacionModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ─── HELPERS ──────────────────────────────────────────────────────────────

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

    // ─── ESCRITURA ────────────────────────────────────────────────────────────

    /**
     * Crea una notificación. Silencia excepciones para no romper el flujo principal.
     *
     * @param string $tipo  'info' | 'success' | 'warning' | 'error'
     */
    public function crear(int $idUsuario, string $titulo, string $mensaje, string $tipo = 'info', string $url = ''): void {
        $sql = "INSERT INTO NOTIFICACION
                       (ID_NOTIFICACION, ID_USUARIO, TITULO, MENSAJE, TIPO, LEIDA, URL, FECHA_CREACION)
                VALUES (SEQ_NOTIFICACION.NEXTVAL, :id_usuario, :titulo, :mensaje, :tipo, 0, :url, SYSDATE)";

        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            $err = oci_error($this->conn);
            error_log('NotificacionModel::crear (parse) – ' . ($err['message'] ?? 'Unknown error'));
            return;
        }
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':titulo',     $titulo);
        oci_bind_by_name($stmt, ':mensaje',    $mensaje);
        oci_bind_by_name($stmt, ':tipo',       $tipo);
        oci_bind_by_name($stmt, ':url',        $url);
        
        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $err = oci_error($stmt);
            error_log('NotificacionModel::crear (execute) – ' . ($err['message'] ?? 'Unknown error'));
        }
        oci_free_statement($stmt);
    }

    public function marcarTodas(int $idUsuario): void {
        $sql  = "UPDATE NOTIFICACION SET LEIDA = 1 WHERE ID_USUARIO = :id_usuario AND LEIDA = 0";
        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            return;
        }
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt, OCI_NO_AUTO_COMMIT);
        oci_free_statement($stmt);
    }

    // ─── LECTURA ──────────────────────────────────────────────────────────────

    public function obtenerParaUsuario(int $idUsuario, int $limite = 15): array {
        $sql = "SELECT ID_NOTIFICACION, TITULO, MENSAJE, TIPO, LEIDA, URL,
                       TO_CHAR(FECHA_CREACION,'DD/MM/YYYY HH24:MI') AS FECHA_CREACION
                  FROM NOTIFICACION
                 WHERE ID_USUARIO = :id_usuario
                 ORDER BY FECHA_CREACION DESC, ID_NOTIFICACION DESC
                 FETCH FIRST :limite ROWS ONLY";

        $stmt = @oci_parse($this->conn, $sql);
        if (!$stmt) {
            return [];
        }
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':limite',     $limite,    -1, SQLT_INT);
        oci_set_prefetch($stmt, max(15, $limite));
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_free_statement($stmt);
            return [];
        }
        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $this->normalizeRow($row);
        }
        oci_free_statement($stmt);
        return $rows;
    }

    public function contarNoLeidas(int $idUsuario): int {
        $sql  = "SELECT COUNT(*) AS TOTAL FROM NOTIFICACION WHERE ID_USUARIO = :id_usuario AND LEIDA = 0";
        $stmt = @oci_parse($this->conn, $sql);
        if (!$stmt) {
            return 0;
        }
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_free_statement($stmt);
            return 0;
        }
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        return (int) ($row['TOTAL'] ?? 0);
    }

    // ─── UTILIDADES ───────────────────────────────────────────────────────────

    /** Devuelve el ID del primer usuario administrador (tipo 1). */
    public function obtenerIdAdmin(): ?int {
        $sql  = "SELECT MIN(u.ID_USUARIO) AS ID_ADMIN
                   FROM USUARIO u
                  WHERE u.ID_TIPO = 1";
        $stmt = @oci_parse($this->conn, $sql);
        if (!$stmt) {
            return null;
        }
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_free_statement($stmt);
            return null;
        }
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        $id  = (int) ($row['ID_ADMIN'] ?? 0);
        return $id > 0 ? $id : null;
    }
}
