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

    private function logOracleError($stmt = null, string $context = 'Oracle'): void {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        if (!$error) {
            error_log($context . ': Error de Oracle desconocido');
            return;
        }

        error_log($context . ': ' . json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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

    private function argumentosProcedimiento(string $procedimiento): array {
        $query = "SELECT ARGUMENT_NAME,
                         POSITION,
                         IN_OUT,
                         DATA_TYPE
                  FROM USER_ARGUMENTS
                  WHERE OBJECT_NAME = :procedimiento
                  AND PACKAGE_NAME IS NULL
                  ORDER BY POSITION";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        $procedimiento = strtoupper($procedimiento);
        oci_bind_by_name($stmt, ':procedimiento', $procedimiento);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $argumentos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            if (empty($row['ARGUMENT_NAME'])) {
                continue;
            }

            $argumentos[] = [
                'name' => strtoupper($row['ARGUMENT_NAME']),
                'position' => (int) $row['POSITION'],
                'in_out' => strtoupper($row['IN_OUT'] ?? 'IN'),
                'data_type' => strtoupper($row['DATA_TYPE'] ?? '')
            ];
        }
        oci_free_statement($stmt);

        return $argumentos;
    }

    public function crearPedidoCompletoTx(int $idUsuario, int $idDireccionPedido, int $idMetodoPago, ?string $fechaEstimadaEntrega, array $resumen): array {
        if ($idUsuario <= 0 || $idDireccionPedido <= 0) {
            throw new InvalidArgumentException('Usuario o direccion invalida');
        }

        $argumentos = $this->argumentosProcedimiento('SP_CREAR_PEDIDO_COMPLETO');
        if (empty($argumentos)) {
            $argumentos = [
                ['name' => 'P_ID_USUARIO', 'in_out' => 'IN', 'data_type' => 'NUMBER'],
                ['name' => 'P_ID_DIRECCION_PEDIDO', 'in_out' => 'IN', 'data_type' => 'NUMBER'],
                ['name' => 'P_ID_METODO_PAGO', 'in_out' => 'IN', 'data_type' => 'NUMBER'],
                ['name' => 'P_ID_PEDIDO', 'in_out' => 'OUT', 'data_type' => 'NUMBER'],
                ['name' => 'P_ID_VENTA', 'in_out' => 'OUT', 'data_type' => 'NUMBER']
            ];
        }

        $placeholders = [];
        foreach ($argumentos as $arg) {
            $placeholders[] = ':' . $arg['name'];
        }

        $query = "BEGIN SP_CREAR_PEDIDO_COMPLETO(" . implode(', ', $placeholders) . "); END;";
        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            $this->logOracleError(null, 'PedidoModel::crearPedidoCompletoTx oci_parse SP_CREAR_PEDIDO_COMPLETO');
            throw new Exception($this->oracleErrorMessage());
        }

        $bindValues = [];
        $out = [
            'id_pedido' => 0,
            'id_venta' => 0
        ];

        foreach ($argumentos as $arg) {
            $name = $arg['name'];
            $key = ':' . $name;
            $isOut = str_contains($arg['in_out'], 'OUT');
            $value = null;
            $type = SQLT_CHR;
            $length = -1;

            if (str_contains($name, 'USUARIO')) {
                $value = $idUsuario;
                $type = SQLT_INT;
            } elseif (str_contains($name, 'DIRECCION')) {
                $value = $idDireccionPedido;
                $type = SQLT_INT;
            } elseif (str_contains($name, 'METODO') || str_contains($name, 'PAGO')) {
                $value = $idMetodoPago;
                $type = SQLT_INT;
            } elseif (str_contains($name, 'FECHA')) {
                $value = $fechaEstimadaEntrega;
                $type = SQLT_CHR;
            } elseif (str_contains($name, 'TOTAL')) {
                $value = number_format((float) ($resumen['total'] ?? 0), 2, '.', '');
                $type = SQLT_CHR;
            } elseif (str_contains($name, 'IVA')) {
                $value = number_format((float) ($resumen['iva'] ?? 0), 2, '.', '');
                $type = SQLT_CHR;
            } elseif (str_contains($name, 'ENVIO')) {
                $value = number_format((float) ($resumen['envio'] ?? 0), 2, '.', '');
                $type = SQLT_CHR;
            } elseif (str_contains($name, 'SUBTOTAL')) {
                $value = number_format((float) ($resumen['subtotal'] ?? 0), 2, '.', '');
                $type = SQLT_CHR;
            }

            if ($isOut) {
                $length = 64;
                if (str_contains($name, 'PEDIDO')) {
                    $out['id_pedido'] = 0;
                    oci_bind_by_name($stmt, $key, $out['id_pedido'], $length, SQLT_INT);
                    continue;
                }
                if (str_contains($name, 'VENTA')) {
                    $out['id_venta'] = 0;
                    oci_bind_by_name($stmt, $key, $out['id_venta'], $length, SQLT_INT);
                    continue;
                }
            }

            $bindValues[$key] = $value;
            oci_bind_by_name($stmt, $key, $bindValues[$key], $length, $type);
        }

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $this->logOracleError($stmt, 'PedidoModel::crearPedidoCompletoTx oci_execute SP_CREAR_PEDIDO_COMPLETO');
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);

        return [
            'id_pedido' => (int) $out['id_pedido'],
            'id_venta' => (int) $out['id_venta']
        ];
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

    public function obtenerPorId($idPedido): ?array {
        $idPedido = (int) $idPedido;
        if ($idPedido <= 0) {
            throw new InvalidArgumentException('Pedido invalido');
        }

        $query = "SELECT ID_PEDIDO,
                         ID_VENTA,
                         ID_ESTADO,
                         FECHA_ESTIMADA_ENTREGA,
                         ID_DIRECCION_PEDIDO
                  FROM PEDIDO
                  WHERE ID_PEDIDO = :id_pedido
                  FETCH FIRST 1 ROWS ONLY";

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

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }
}
