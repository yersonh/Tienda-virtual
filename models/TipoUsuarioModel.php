<?php
class TipoUsuarioModel {

    private $conn;
    private $table = 'tipo_usuario';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $stmt = oci_parse($this->conn, "SELECT id_tipo, nombre FROM tipo_usuario ORDER BY nombre");
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $results;
    }
}
