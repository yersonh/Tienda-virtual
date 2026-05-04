<?php

class VentaModel {

    private $conn;
    private $columnasCache = [];
    private $secuenciasCache = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    private function columnasTabla(string $tabla): array {
        $tabla = strtoupper($tabla);
        if (isset($this->columnasCache[$tabla])) {
            return $this->columnasCache[$tabla];
        }

        $query = "SELECT COLUMN_NAME
                  FROM USER_TAB_COLUMNS
                  WHERE TABLE_NAME = :tabla";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':tabla', $tabla);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $columnas = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $columnas[strtolower($row['COLUMN_NAME'])] = $row['COLUMN_NAME'];
        }
        oci_free_statement($stmt);

        $this->columnasCache[$tabla] = $columnas;
        return $columnas;
    }

    private function columnaExiste(string $tabla, string $columna): bool {
        $columnas = $this->columnasTabla($tabla);
        return isset($columnas[strtolower($columna)]);
    }

    private function secuenciaDisponible(array $candidatas): ?string {
        $cacheKey = implode('|', array_map('strtoupper', $candidatas));
        if (array_key_exists($cacheKey, $this->secuenciasCache)) {
            return $this->secuenciasCache[$cacheKey];
        }

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
                $this->secuenciasCache[$cacheKey] = $row['SEQUENCE_NAME'];
                return $this->secuenciasCache[$cacheKey];
            }
        }

        $this->secuenciasCache[$cacheKey] = null;
        return $this->secuenciasCache[$cacheKey];
    }

    private function numeroOracle(float $valor): string {
        return number_format($valor, 2, '.', '');
    }

    private function obtenerIdClienteUsuarioTx(int $idUsuario): ?int {
        if (!$this->columnaExiste('USUARIO', 'ID_PERSONA')) {
            return null;
        }

        $query = "SELECT c.ID_CLIENTE
                  FROM USUARIO u
                  INNER JOIN CLIENTE c ON c.ID_PERSONA = u.ID_PERSONA
                  WHERE u.ID_USUARIO = :id_usuario
                  FETCH FIRST 1 ROWS ONLY";

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

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row && isset($row['ID_CLIENTE']) ? (int) $row['ID_CLIENTE'] : null;
    }

    public function crearVenta($idUsuario): int {
        $idUsuario = (int) $idUsuario;
        if ($idUsuario <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        $query = "BEGIN PC_CREAR_VENTA(:p_id_usuario, :p_id_venta); END;";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        $idVenta = null;
        oci_bind_by_name($stmt, ':p_id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);

        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new Exception('PC_CREAR_VENTA no retorno un ID_VENTA valido');
        }

        return $idVenta;
    }

    public function crearVentaCheckoutTx(int $idUsuario, float $total, float $iva, float $envio): int {
        $idUsuario = (int) $idUsuario;
        $total = round(max(0, $total), 2);
        $iva = max(0, $iva);
        $envio = max(0, $envio);

        if ($idUsuario <= 0) {
            throw new InvalidArgumentException('Usuario invalido');
        }

        if ($total <= 0) {
            throw new InvalidArgumentException('La venta no tiene un total valido');
        }

        $columnas = [];
        $valores = [];
        $binds = [
            ':id_usuario' => ['value' => $idUsuario, 'type' => SQLT_INT],
            ':iva' => ['value' => $this->numeroOracle($iva), 'type' => SQLT_CHR],
            ':envio' => ['value' => $this->numeroOracle($envio), 'type' => SQLT_CHR],
            ':total' => ['value' => $this->numeroOracle($total), 'type' => SQLT_CHR],
        ];

        $secuenciaVenta = $this->secuenciaDisponible(['SEQ_VENTA']);
        if ($secuenciaVenta !== null) {
            $columnas[] = 'ID_VENTA';
            $valores[] = $secuenciaVenta . '.NEXTVAL';
        }

        array_push($columnas, 'ID_USUARIO', 'IVA', 'ENVIO', 'TOTAL', 'FECHA');
        array_push($valores, ':id_usuario', ':iva', ':envio', ':total', 'SYSDATE');

        if ($this->columnaExiste('VENTA', 'ID_TIPO')) {
            $idTipo = 2;
            $columnas[] = 'ID_TIPO';
            $valores[] = ':id_tipo';
            $binds[':id_tipo'] = ['value' => $idTipo, 'type' => SQLT_INT];
        }

        if ($this->columnaExiste('VENTA', 'ID_CLIENTE')) {
            $idCliente = $this->obtenerIdClienteUsuarioTx($idUsuario);
            if ($idCliente !== null) {
                $columnas[] = 'ID_CLIENTE';
                $valores[] = ':id_cliente';
                $binds[':id_cliente'] = ['value' => $idCliente, 'type' => SQLT_INT];
            }
        }

        if ($this->columnaExiste('VENTA', 'ESTADO')) {
            $estado = 'PENDIENTE';
            $columnas[] = 'ESTADO';
            $valores[] = ':estado';
            $binds[':estado'] = ['value' => $estado, 'type' => SQLT_CHR];
        }

        $query = "INSERT INTO VENTA (" . implode(', ', $columnas) . ")
                  VALUES (" . implode(', ', $valores) . ")
                  RETURNING ID_VENTA INTO :id_venta";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        $idVenta = null;
        $bindValues = [];
        foreach ($binds as $placeholder => $bind) {
            $bindValues[$placeholder] = $bind['value'];
            oci_bind_by_name($stmt, $placeholder, $bindValues[$placeholder], -1, $bind['type']);
        }
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);

        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new Exception('No se pudo obtener el ID de la venta');
        }

        return $idVenta;
    }

    public function insertarDetalleVentaTx(int $idVenta, int $idProducto, int $cantidad, float $precioUnitario, ?int $idReferencia = null): void {
        if ($idVenta <= 0 || $idProducto <= 0 || $cantidad <= 0 || $precioUnitario < 0) {
            throw new InvalidArgumentException('Detalle de venta invalido');
        }

        $subtotal = round($precioUnitario * $cantidad, 2);
        $precio = $this->numeroOracle($precioUnitario);
        $subtotalLinea = $this->numeroOracle($subtotal);

        $columnas = ['ID_VENTA', 'ID_PRODUCTO', 'CANTIDAD', 'PRECIO_UNITARIO', 'SUBTOTAL'];
        $valores = [':id_venta', ':id_producto', ':cantidad', ':precio_unitario', ':subtotal'];
        if ($idReferencia !== null && $idReferencia > 0) {
            $columnas[] = 'ID_REFERENCIA';
            $valores[] = ':id_referencia';
        }

        $secuenciaDetalle = $this->secuenciaDisponible(['SEQ_DETALLE_VENTA', 'SEQ_DETALLE']);
        if ($secuenciaDetalle !== null) {
            array_unshift($columnas, 'ID_DETALLE');
            array_unshift($valores, $secuenciaDetalle . '.NEXTVAL');
        }

        $query = "INSERT INTO DETALLE_VENTA (" . implode(', ', $columnas) . ")
                  VALUES (" . implode(', ', $valores) . ")";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':cantidad', $cantidad, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':precio_unitario', $precio, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':subtotal', $subtotalLinea, -1, SQLT_CHR);
        if ($idReferencia !== null && $idReferencia > 0) {
            oci_bind_by_name($stmt, ':id_referencia', $idReferencia, -1, SQLT_INT);
        }

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
    }

    public function obtenerTotalVenta($idVenta): float {
        $idVenta = (int) $idVenta;
        if ($idVenta <= 0) {
            throw new InvalidArgumentException('Venta invalida');
        }

        $query = "SELECT NVL(FN_TOTAL_VENTA(:id_venta), 0) AS TOTAL
                  FROM DUAL";

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

        return (float) ($row['TOTAL'] ?? 0);
    }
}
