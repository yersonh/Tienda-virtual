<?php
class PersonaModel {

    private $conn;
    private $table = 'persona';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear($data) {
        $query = "INSERT INTO persona (nombres, apellidos, cc, correo, telefono, direccion)
                  VALUES (:nombres, :apellidos, :cc, :correo, :telefono, :direccion)
                  RETURNING id_persona INTO :id_persona";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':nombres', $data['nombres']);
        oci_bind_by_name($stmt, ':apellidos', $data['apellidos']);
        oci_bind_by_name($stmt, ':cc', $data['cc']);
        oci_bind_by_name($stmt, ':correo', $data['correo']);
        oci_bind_by_name($stmt, ':telefono', $data['telefono']);
        oci_bind_by_name($stmt, ':direccion', $data['direccion']);
        $id_persona = null;
        oci_bind_by_name($stmt, ':id_persona', $id_persona, -1, SQLT_INT);
        oci_execute($stmt);
        oci_fetch($stmt);
        oci_free_statement($stmt);

        return ['id_persona' => (int)$id_persona];
    }

    public function obtenerUltimoId() {
        // OCI8 no soporta lastInsertId; usar RETURNING en las consultas
        return null;
    }
}
