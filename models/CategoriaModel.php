<?php
class CategoriaModel {

    private $conn;
    private $table = 'categoria_producto';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodas() {
        $stmt = oci_parse($this->conn, "SELECT * FROM categoria_producto");
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $results;
    }
}