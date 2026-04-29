<?php

class DireccionPedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function normalizarFila(array $row): array {
        return array_change_key_case($row, CASE_LOWER);
    }

    public function obtenerDirecciones($idUsuario): array {
        $idUsuario = (int) $idUsuario;

        $query = "SELECT ID_DIRECCION_PEDIDO,
                         ID_USUARIO,
                         NOMBRE,
                         APELLIDO,
                         DIRECCION,
                         CIUDAD,
                         BARRIO,
                         TELEFONO,
                         NVL(ES_PREDETERMINADA, 0) AS ES_PREDETERMINADA
                  FROM DIRECCION_PEDIDO
                  WHERE ID_USUARIO = :id_usuario
                  ORDER BY NVL(ES_PREDETERMINADA, 0) DESC, ID_DIRECCION_PEDIDO DESC";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $direcciones = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $direcciones[] = $this->normalizarFila($row);
        }

        oci_free_statement($stmt);

        return $direcciones;
    }

    public function guardarDireccion($data): array {
        $idUsuario = (int) ($data['id_usuario'] ?? 0);
        $nombre = trim($data['nombre'] ?? '');
        $apellido = trim($data['apellido'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $ciudad = trim($data['ciudad'] ?? '');
        $barrio = trim($data['barrio'] ?? '');
        $telefono = preg_replace('/\D/', '', (string) ($data['telefono'] ?? ''));
        $esPredeterminada = !empty($data['es_predeterminada']) ? 1 : 0;

        if ($idUsuario <= 0 || $nombre === '' || $apellido === '' || $direccion === '' || $ciudad === '' || $barrio === '' || $telefono === '') {
            return ['success' => false, 'message' => 'Todos los campos de direccion son obligatorios'];
        }

        try {
            if ($esPredeterminada === 1) {
                $this->quitarPredeterminada($idUsuario);
            }

            $query = "INSERT INTO DIRECCION_PEDIDO
                        (ID_USUARIO, NOMBRE, APELLIDO, DIRECCION, CIUDAD, BARRIO, TELEFONO, ES_PREDETERMINADA)
                      VALUES
                        (:id_usuario, :nombre, :apellido, :direccion, :ciudad, :barrio, :telefono, :es_predeterminada)
                      RETURNING ID_DIRECCION_PEDIDO INTO :id_direccion";

            $stmt = oci_parse($this->conn, $query);
            $idDireccion = null;

            oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':nombre', $nombre);
            oci_bind_by_name($stmt, ':apellido', $apellido);
            oci_bind_by_name($stmt, ':direccion', $direccion);
            oci_bind_by_name($stmt, ':ciudad', $ciudad);
            oci_bind_by_name($stmt, ':barrio', $barrio);
            oci_bind_by_name($stmt, ':telefono', $telefono);
            oci_bind_by_name($stmt, ':es_predeterminada', $esPredeterminada, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_direccion', $idDireccion, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                oci_rollback($this->conn);
                return ['success' => false, 'message' => $error['message'] ?? 'No se pudo guardar la direccion'];
            }

            oci_free_statement($stmt);
            oci_commit($this->conn);

            return [
                'success' => true,
                'id_direccion' => (int) $idDireccion
            ];
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());

            return ['success' => false, 'message' => 'No se pudo guardar la direccion'];
        }
    }

    public function obtenerDireccionPorId($id): ?array {
        $id = (int) $id;

        $query = "SELECT ID_DIRECCION_PEDIDO,
                         ID_USUARIO,
                         NOMBRE,
                         APELLIDO,
                         DIRECCION,
                         CIUDAD,
                         BARRIO,
                         TELEFONO,
                         NVL(ES_PREDETERMINADA, 0) AS ES_PREDETERMINADA
                  FROM DIRECCION_PEDIDO
                  WHERE ID_DIRECCION_PEDIDO = :id";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $id, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? $this->normalizarFila($row) : null;
    }

    private function quitarPredeterminada($idUsuario): void {
        $query = "UPDATE DIRECCION_PEDIDO
                  SET ES_PREDETERMINADA = 0
                  WHERE ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo actualizar la direccion predeterminada');
        }

        oci_free_statement($stmt);
    }
}
