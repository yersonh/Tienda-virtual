<?php

class PedidoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    private function secuenciaDisponible(array $candidatas): ?string {
        foreach ($candidatas as $secuencia) {
            $query = "SELECT SEQUENCE_NAME
                      FROM USER_SEQUENCES
                      WHERE SEQUENCE_NAME = :secuencia
                      FETCH FIRST 1 ROWS ONLY";

            $stmt = oci_parse($this->conn, $query);
            if (!$stmt) {
                throw new Exception($this->oracleErrorMessage());
            }

            $secuenciaUpper = strtoupper($secuencia);
            oci_bind_by_name($stmt, ':secuencia', $secuenciaUpper);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $message = $this->oracleErrorMessage($stmt);
                oci_free_statement($stmt);
                throw new Exception($message);
            }

            $row = oci_fetch_assoc($stmt);
            oci_free_statement($stmt);

            if ($row && !empty($row['SEQUENCE_NAME'])) {
                return $row['SEQUENCE_NAME'];
            }
        }

        return null;
    }

    public function crearPedidoTx(int $idVenta, ?string $fechaEstimadaEntrega = null): int {
        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        $columnas = ['ID_VENTA', 'ID_ESTADO', 'FECHA_ESTIMADA_ENTREGA'];
        $valores = [
            ':id_venta',
            '1',
            $fechaEstimadaEntrega ? "TO_DATE(:fecha_estimada, 'YYYY-MM-DD')" : 'NULL'
        ];

        $secuenciaPedido = $this->secuenciaDisponible(['SEQ_PEDIDO']);
        if ($secuenciaPedido !== null) {
            array_unshift($columnas, 'ID_PEDIDO');
            array_unshift($valores, $secuenciaPedido . '.NEXTVAL');
        }

        $query = "INSERT INTO PEDIDO (" . implode(', ', $columnas) . ")
                  VALUES (" . implode(', ', $valores) . ")
                  RETURNING ID_PEDIDO INTO :id_pedido";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        $idPedido = null;
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        if ($fechaEstimadaEntrega) {
            oci_bind_by_name($stmt, ':fecha_estimada', $fechaEstimadaEntrega);
        }
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);

        $idPedido = (int) $idPedido;
        if ($idPedido <= 0) {
            throw new Exception('No se pudo obtener el ID del pedido');
        }

        return $idPedido;
    }

    public function actualizarDireccionPedidoTx(int $idPedido, int $idDireccionPedido): void {
        $idPedido = (int) $idPedido;
        $idDireccionPedido = (int) $idDireccionPedido;
        if ($idPedido <= 0 || $idDireccionPedido <= 0) {
            throw new InvalidArgumentException('Pedido o direccion invalida');
        }

        $query = "UPDATE PEDIDO
                  SET ID_DIRECCION_PEDIDO = :id_direccion_pedido
                  WHERE ID_PEDIDO = :id_pedido";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_direccion_pedido', $idDireccionPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $filas = oci_num_rows($stmt);
        oci_free_statement($stmt);

        if ($filas < 1) {
            throw new Exception('No se pudo asociar la direccion al pedido');
        }
    }

    public function mantenerPendienteTx(int $idPedido): void {
        $idPedido = (int) $idPedido;
        if ($idPedido <= 0) {
            throw new InvalidArgumentException('Pedido invalido');
        }

        $query = "UPDATE PEDIDO
                  SET ID_ESTADO = 1
                  WHERE ID_PEDIDO = :id_pedido
                    AND ID_ESTADO IN (1, 2)";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
    }

    public function obtenerPorVenta($idVenta): ?array {
        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        $query = "SELECT ID_PEDIDO,
                         ID_VENTA,
                         ID_ESTADO,
                         FECHA_ESTIMADA_ENTREGA,
                         ID_DIRECCION_PEDIDO
                  FROM PEDIDO
                  WHERE ID_VENTA = :id_venta
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }
}
