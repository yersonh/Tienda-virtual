<?php

class OCI8Statement {
    private $stmt;

    public function __construct($stmt) {
        $this->stmt = $stmt;
    }

    public function execute(array $params = []): bool {
        foreach ($params as $key => &$value) {
            $bindKey = ltrim((string)$key, ':');
            oci_bind_by_name($this->stmt, ":$bindKey", $value, -1);
        }
        unset($value);

        $result = oci_execute($this->stmt, OCI_COMMIT_ON_SUCCESS);
        if (!$result) {
            $error = oci_error($this->stmt);
            throw new Exception("OCI8 execute error: " . ($error['message'] ?? 'desconocido'));
        }
        return true;
    }

    public function fetch(int $_ = 0): array|false {
        $row = oci_fetch_assoc($this->stmt);
        if ($row === false) return false;
        return array_change_key_case($row, CASE_LOWER);
    }

    public function fetchAll(int $_ = 0): array {
        $results = [];
        while ($row = oci_fetch_assoc($this->stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }
        return $results;
    }

    public function fetchColumn(int $col = 0): mixed {
        $row = oci_fetch_array($this->stmt, OCI_NUM + OCI_RETURN_NULLS);
        return $row ? $row[$col] : false;
    }

    public function rowCount(): int {
        return oci_num_rows($this->stmt);
    }
}

class OCI8Connection {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function prepare(string $sql): OCI8Statement {
        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            $error = oci_error($this->conn);
            throw new Exception("OCI8 parse error: " . ($error['message'] ?? 'desconocido'));
        }
        return new OCI8Statement($stmt);
    }

    public function query(string $sql): OCI8Statement {
        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            $error = oci_error($this->conn);
            throw new Exception("OCI8 parse error: " . ($error['message'] ?? 'desconocido'));
        }
        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        if (!$result) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception("OCI8 query error: " . ($error['message'] ?? 'desconocido'));
        }
        return new OCI8Statement($stmt);
    }

    public function exec(string $sql): bool {
        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) return false;
        $result = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmt);
        return (bool) $result;
    }

    public function lastInsertId(): mixed {
        // Oracle usa RETURNING INTO, no lastInsertId
        return false;
    }
}
