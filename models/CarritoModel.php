<?php

class CarritoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    private function oracleErrorMessage($stmt = null): string {
        $error = $stmt ? oci_error($stmt) : oci_error($this->conn);
        return $error['message'] ?? 'Error de Oracle desconocido';
    }

    private function failure(string $message): array {
        error_log($message);
        return ['success' => false, 'message' => $message];
    }

    private function success(): array {
        return ['success' => true];
    }

    private function validarIdsYCantidad($idUsuario, $idProducto = null, $cantidad = 1, $idReferencia = null): array {
        $idUsuario = (int) $idUsuario;
        $idProducto = $idProducto === null ? null : (int) $idProducto;
        $idReferencia = $idReferencia === null ? null : (int) $idReferencia;
        $cantidad = (int) $cantidad;

        if ($idUsuario <= 0) {
            throw new Exception('Usuario invalido');
        }

        if ($idProducto !== null && $idProducto <= 0) {
            throw new Exception('Producto invalido');
        }

        if ($idReferencia !== null && $idReferencia <= 0) {
            throw new Exception('Referencia invalida');
        }

        if ($cantidad <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }

        return [$idUsuario, $idProducto, $cantidad, $idReferencia];
    }

    public function obtenerIdCarritoUsuario($idUsuario) {
        $idUsuario = (int) $idUsuario;

        $query = "SELECT ID_CARRITO
                  FROM CARRITO
                  WHERE ID_USUARIO = :ID_USUARIO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? (int) $row['ID_CARRITO'] : null;
    }

    private function obtenerOCrearCarritoUsuarioTx($idUsuario): int {
        $idUsuario = (int) $idUsuario;

        $query = "SELECT ID_CARRITO
                  FROM CARRITO
                  WHERE ID_USUARIO = :ID_USUARIO
                  FOR UPDATE";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            throw new Exception($this->oracleErrorMessage($stmt));
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row) {
            return (int) $row['ID_CARRITO'];
        }

        $query = "INSERT INTO CARRITO (ID_USUARIO)
                  VALUES (:ID_USUARIO)
                  RETURNING ID_CARRITO INTO :ID_CARRITO";

        $stmt = oci_parse($this->conn, $query);
        $idCarrito = null;
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);

            if (str_contains($message, 'ORA-00001')) {
                $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
                if ($idCarrito) {
                    return (int) $idCarrito;
                }
            }

            throw new Exception($message);
        }

        oci_free_statement($stmt);
        return (int) $idCarrito;
    }

    public function obtenerOCrearCarritoUsuario($idUsuario) {
        try {
            [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);
            oci_commit($this->conn);
            return $idCarrito;
        } catch (Exception $e) {
            oci_rollback($this->conn);
            throw $e;
        }
    }

    private function agregarDetalleEnCarritoTx($idCarrito, $idProducto, $idReferencia, $cantidad): void {
        $query = "SELECT ID_DETALLE, CANTIDAD
                  FROM DETALLE_CARRITO
                  WHERE ID_CARRITO = :ID_CARRITO
                  AND ID_PRODUCTO = :ID_PRODUCTO
                  AND ID_REFERENCIA = :ID_REFERENCIA
                  FOR UPDATE";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            throw new Exception($this->oracleErrorMessage($stmt));
        }

        $detalle = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($detalle) {
            $query = "UPDATE DETALLE_CARRITO
                      SET CANTIDAD = CANTIDAD + :CANTIDAD,
                          SELECCIONADO = 1
                      WHERE ID_DETALLE = :ID_DETALLE";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_DETALLE', $detalle['ID_DETALLE'], -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }

            oci_free_statement($stmt);
            return;
        }

        $query = "INSERT INTO DETALLE_CARRITO (ID_CARRITO, ID_PRODUCTO, ID_REFERENCIA, CANTIDAD, SELECCIONADO)
                  VALUES (:ID_CARRITO, :ID_PRODUCTO, :ID_REFERENCIA, :CANTIDAD, 1)";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);
    }

    public function agregarAlCarrito($idUsuario, $idProducto, $cantidad, $idReferencia = null) {
        try {
            [$idUsuario, $idProducto, $cantidad, $idReferencia] = $this->validarIdsYCantidad($idUsuario, $idProducto, $cantidad, $idReferencia);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);
            $this->agregarDetalleEnCarritoTx($idCarrito, $idProducto, $idReferencia, $cantidad);
            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function agregarProducto($idUsuario, $idProducto, $cantidad, $idReferencia = null) {
        return $this->agregarAlCarrito($idUsuario, $idProducto, $cantidad, $idReferencia);
    }

    public function actualizarCantidad($idUsuario, $idProducto, $cantidad, $idReferencia = null) {
        try {
            [$idUsuario, $idProducto, $cantidad, $idReferencia] = $this->validarIdsYCantidad($idUsuario, $idProducto, max(1, (int) $cantidad), $idReferencia);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);

            $query = "UPDATE DETALLE_CARRITO
                      SET CANTIDAD = :CANTIDAD
                      WHERE ID_CARRITO = :ID_CARRITO
                      AND ID_PRODUCTO = :ID_PRODUCTO
                      AND ID_REFERENCIA = :ID_REFERENCIA";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }

            $filasActualizadas = oci_num_rows($stmt);
            oci_free_statement($stmt);

            if ($filasActualizadas === 0) {
                $this->agregarDetalleEnCarritoTx($idCarrito, $idProducto, $idReferencia, $cantidad);
            }

            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function eliminarDelCarrito($idUsuario, $idProducto, $idReferencia = null) {
        try {
            [$idUsuario, $idProducto,, $idReferencia] = $this->validarIdsYCantidad($idUsuario, $idProducto, 1, $idReferencia);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);

            $query = "DELETE FROM DETALLE_CARRITO
                      WHERE ID_CARRITO = :ID_CARRITO
                      AND ID_PRODUCTO = :ID_PRODUCTO
                      AND ID_REFERENCIA = :ID_REFERENCIA";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }

            oci_free_statement($stmt);
            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function eliminarProducto($idUsuario, $idProducto, $idReferencia = null) {
        return $this->eliminarDelCarrito($idUsuario, $idProducto, $idReferencia);
    }

    public function actualizarSeleccion($idUsuario, $idReferencia, $seleccionado) {
        try {
            [$idUsuario,,, $idReferencia] = $this->validarIdsYCantidad($idUsuario, null, 1, $idReferencia);
            $seleccionado = (int) $seleccionado === 1 ? 1 : 0;
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);

            $query = "UPDATE DETALLE_CARRITO
                      SET SELECCIONADO = :SELECCIONADO
                      WHERE ID_CARRITO = :ID_CARRITO
                      AND ID_REFERENCIA = :ID_REFERENCIA";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':SELECCIONADO', $seleccionado, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }

            oci_free_statement($stmt);
            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function vaciarCarrito($idUsuario) {
        try {
            $this->vaciarCarritoTx($idUsuario);
            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function vaciarCarritoTx($idUsuario): void {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return;
        }

        $query = "DELETE FROM DETALLE_CARRITO
                  WHERE ID_CARRITO = :ID_CARRITO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($error);
        }

        oci_free_statement($stmt);
    }

    public function eliminarSeleccionadosTx($idUsuario): void {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return;
        }

        $query = "DELETE FROM DETALLE_CARRITO
                  WHERE ID_CARRITO = :ID_CARRITO
                  AND NVL(SELECCIONADO, 0) = 1";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($error);
        }

        oci_free_statement($stmt);
    }

    private function itemsBaseQuery(bool $soloSeleccionados = false, bool $forUpdate = false): string {
        $whereSeleccion = $soloSeleccionados ? "AND NVL(dc.SELECCIONADO, 0) = 1" : "";
        $forUpdateSql = $forUpdate ? "FOR UPDATE OF dc.CANTIDAD, dc.SELECCIONADO" : "";

        return "SELECT c.ID_CARRITO,
                       dc.ID_DETALLE,
                       dc.ID_PRODUCTO,
                       dc.ID_REFERENCIA,
                       dc.CANTIDAD,
                       NVL(dc.SELECCIONADO, 0) AS SELECCIONADO,
                       p.NOMBRE,
                       p.CODIGO,
                       p.DESCRIPCION,
                       p.PRECIO,
                       p.ID_CATEGORIA,
                       cp.NOMBRE AS CATEGORIA_NOMBRE,
                       r.NUMERO_REFERENCIA,
                       r.MARCA,
                       r.FABRICANTE,
                       NVL(stk.STOCK_P, 0) AS STOCK_P,
                       (p.PRECIO * dc.CANTIDAD) AS SUBTOTAL,
                       img.IMAGEN
                FROM CARRITO c
                INNER JOIN DETALLE_CARRITO dc ON dc.ID_CARRITO = c.ID_CARRITO
                INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = dc.ID_PRODUCTO
                INNER JOIN REFERENCIA_PRODUCTO r ON r.ID_REFERENCIA = dc.ID_REFERENCIA
                    AND r.ID_PRODUCTO = p.ID_PRODUCTO
                INNER JOIN CATEGORIA_PRODUCTO cp ON cp.ID_CATEGORIA = p.ID_CATEGORIA
                LEFT JOIN (
                    SELECT x.ID_REFERENCIA,
                           SUM(x.STOCK_P) AS STOCK_P
                    FROM (
                        SELECT cv.ID_REFERENCIA,
                               NVL(cv.STOCK_P, 0) AS STOCK_P
                        FROM COMPATIBILIDAD_VEHICULO cv
                        UNION ALL
                        SELECT cm.ID_REFERENCIA,
                               NVL(cm.STOCK_P, 0) AS STOCK_P
                        FROM COMPATIBILIDAD_MAQUINARIA cm
                    ) x
                    GROUP BY x.ID_REFERENCIA
                ) stk ON stk.ID_REFERENCIA = dc.ID_REFERENCIA
                LEFT JOIN (
                    SELECT pi.ID_PRODUCTO,
                           MIN(pi.URL) KEEP (DENSE_RANK FIRST ORDER BY NVL(pi.ORDEN, 999999), pi.ID_IMAGEN) AS IMAGEN
                    FROM PRODUCTO_IMAGEN pi
                    GROUP BY pi.ID_PRODUCTO
                ) img ON img.ID_PRODUCTO = p.ID_PRODUCTO
                WHERE c.ID_USUARIO = :ID_USUARIO
                $whereSeleccion
                ORDER BY p.NOMBRE, r.NUMERO_REFERENCIA
                $forUpdateSql";
    }

    public function obtenerItemsCheckoutTx($idUsuario): array {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        $query = $this->itemsBaseQuery(true, true);

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $items[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $items;
    }

    public function obtenerItemsDetallados($idUsuario, bool $soloSeleccionados = false) {
        return $this->obtenerItemsVisualizacion($idUsuario, $soloSeleccionados);
    }

    public function obtenerItemsVisualizacion($idUsuario, bool $soloSeleccionados = false) {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return [];
        }

        $query = $this->itemsBaseQuery($soloSeleccionados, false);

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $item = array_change_key_case($row, CASE_LOWER);
            $item['id_detalle'] = (int) $item['id_detalle'];
            $item['id_producto'] = (int) $item['id_producto'];
            $item['id_referencia'] = (int) $item['id_referencia'];
            $item['cantidad'] = (int) $item['cantidad'];
            $item['seleccionado'] = (int) $item['seleccionado'];
            $item['precio'] = (float) $item['precio'];
            $item['stock_p'] = (int) $item['stock_p'];
            $item['subtotal'] = (float) $item['subtotal'];
            $item['total_linea'] = (float) $item['subtotal'];
            $results[] = $item;
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerMapaCarritoUsuario($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return [];
        }

        $query = "SELECT ID_REFERENCIA,
                         CANTIDAD
                  FROM DETALLE_CARRITO
                  WHERE ID_CARRITO = :ID_CARRITO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
        oci_execute($stmt);

        $mapa = [];
        while ($item = oci_fetch_assoc($stmt)) {
            $mapa[(int) $item['ID_REFERENCIA']] = (int) $item['CANTIDAD'];
        }
        oci_free_statement($stmt);

        return $mapa;
    }

    public function obtenerResumenCarrito($idUsuario, bool $soloSeleccionados = false) {
        $items = $this->obtenerItemsVisualizacion($idUsuario, $soloSeleccionados);
        return [
            'id_usuario' => (int) $idUsuario,
            'total_items' => array_sum(array_map(fn($item) => (int) ($item['cantidad'] ?? 0), $items)),
            'total_pagar' => array_sum(array_map(fn($item) => (float) ($item['total_linea'] ?? 0), $items))
        ];
    }

    public function obtenerResumenSeleccionadosRapido($idUsuario): array {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return [
                'total_items' => 0,
                'total_pagar' => 0.0
            ];
        }

        $query = "SELECT NVL(SUM(dc.CANTIDAD), 0) AS TOTAL_ITEMS,
                         NVL(SUM(p.PRECIO * dc.CANTIDAD), 0) AS TOTAL_PAGAR
                  FROM DETALLE_CARRITO dc
                  INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = dc.ID_PRODUCTO
                  WHERE dc.ID_CARRITO = :ID_CARRITO
                    AND NVL(dc.SELECCIONADO, 0) = 1";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $row = oci_fetch_assoc($stmt) ?: [];
        oci_free_statement($stmt);

        return [
            'total_items' => (int) ($row['TOTAL_ITEMS'] ?? 0),
            'total_pagar' => (float) ($row['TOTAL_PAGAR'] ?? 0)
        ];
    }

    public function obtenerTotalCarrito($idUsuario, bool $soloSeleccionados = false): float {
        $resumen = $this->obtenerResumenCarrito($idUsuario, $soloSeleccionados);
        return (float) ($resumen['total_pagar'] ?? 0);
    }

    public function obtenerTotalItemsCarrito($idUsuario): int {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        $query = "SELECT NVL(SUM(dc.CANTIDAD), 0) AS TOTAL_ITEMS
                  FROM CARRITO c
                  LEFT JOIN DETALLE_CARRITO dc ON dc.ID_CARRITO = c.ID_CARRITO
                  WHERE c.ID_USUARIO = :ID_USUARIO";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $row = oci_fetch_assoc($stmt) ?: [];
        oci_free_statement($stmt);

        return (int) ($row['TOTAL_ITEMS'] ?? 0);
    }

    public function fusionarCarritoSesion($idUsuario, $carritoSesion) {
        return $this->success();
    }

    public function fusionarCarritoInvitado($idUsuario, $carritoInvitado) {
        return $this->fusionarCarritoSesion($idUsuario, $carritoInvitado);
    }
}
