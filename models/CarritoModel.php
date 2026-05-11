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

    private function esObjetoOracleNoVisible(string $message): bool {
        return str_contains($message, 'ORA-00942');
    }

    private function failure(string $message): array {
        error_log($message);
        return ['success' => false, 'message' => $message];
    }

    private function success(): array {
        return ['success' => true];
    }

    private function normalizarItem(array $row): array {
        $item = array_change_key_case($row, CASE_LOWER);
        $item['id_carrito'] = (int) ($item['id_carrito'] ?? 0);
        $item['id_detalle'] = (int) ($item['id_detalle'] ?? 0);
        $item['id_producto'] = (int) ($item['id_producto'] ?? 0);
        $item['id_referencia'] = isset($item['id_referencia']) ? (int) $item['id_referencia'] : 0;
        $item['cantidad'] = (int) ($item['cantidad'] ?? 0);
        $item['seleccionado'] = (int) ($item['seleccionado'] ?? 0);
        $item['precio'] = (float) ($item['precio'] ?? 0);
        $item['stock_p'] = (int) ($item['stock_p'] ?? 0);
        $item['subtotal'] = (float) ($item['subtotal'] ?? 0);
        $item['total_linea'] = (float) $item['subtotal'];

        return $item;
    }

    private function validarIdsYCantidad($idUsuario, $idProducto = null, $cantidad = 1, $idReferencia = null): array {
        $idUsuario = (int) $idUsuario;
        $idProducto = $idProducto === null ? null : (int) $idProducto;
        $idReferencia = $idReferencia === null ? null : (int) $idReferencia;
        $idReferencia = $idReferencia !== null && $idReferencia <= 0 ? null : $idReferencia;
        $cantidad = (int) $cantidad;

        if ($idUsuario <= 0) {
            throw new Exception('Usuario invalido');
        }

        if ($idProducto !== null && $idProducto <= 0) {
            throw new Exception('Producto invalido');
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

    public function agregarAlCarrito($idUsuario, $idProducto, $cantidad, $idReferencia = null) {
        try {
            [$idUsuario, $idProducto, $cantidad, $idReferencia] = $this->validarIdsYCantidad($idUsuario, $idProducto, $cantidad, $idReferencia);
            $query = "BEGIN SP_AGREGAR_CARRITO(:ID_USUARIO, :ID_PRODUCTO, :ID_REFERENCIA, :CANTIDAD); END;";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);

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

    public function agregarProducto($idUsuario, $idProducto, $cantidad, $idReferencia = null) {
        return $this->agregarAlCarrito($idUsuario, $idProducto, $cantidad, $idReferencia);
    }

    public function actualizarCantidad($idUsuario, $idProducto, $cantidad, $idReferencia = null) {
        try {
            [$idUsuario, $idProducto, $cantidad, $idReferencia] = $this->validarIdsYCantidad($idUsuario, $idProducto, max(1, (int) $cantidad), $idReferencia);
            $query = "BEGIN SP_ACTUALIZAR_CARRITO(:ID_USUARIO, :ID_PRODUCTO, :ID_REFERENCIA, :CANTIDAD); END;";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);

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

    public function eliminarDelCarrito($idUsuario, $idProducto, $idReferencia = null) {
        try {
            [$idUsuario, $idProducto,, $idReferencia] = $this->validarIdsYCantidad($idUsuario, $idProducto, 1, $idReferencia);
            $query = "BEGIN SP_ELIMINAR_CARRITO(:ID_USUARIO, :ID_PRODUCTO, :ID_REFERENCIA); END;";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
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
            if ($idReferencia === null) {
                throw new Exception('Referencia invalida');
            }
            $seleccionado = (int) $seleccionado === 1 ? 1 : 0;

            $query = "UPDATE DETALLE_CARRITO
                      SET SELECCIONADO = :SELECCIONADO
                      WHERE ID_DETALLE IN (
                          SELECT ID_DETALLE
                          FROM V_CARRITO_USUARIO
                          WHERE ID_USUARIO = :ID_USUARIO
                            AND ID_REFERENCIA = :ID_REFERENCIA
                      )";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':SELECCIONADO', $seleccionado, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
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
        $query = "BEGIN SP_VACIAR_CARRITO(:ID_USUARIO); END;";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($error);
        }

        oci_free_statement($stmt);
    }

    public function eliminarSeleccionadosTx($idUsuario): void {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        foreach ($this->obtenerItemsCheckoutTx($idUsuario) as $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $idReferencia = (int) ($item['id_referencia'] ?? 0);
            $idReferencia = $idReferencia > 0 ? $idReferencia : null;
            if ($idProducto <= 0) {
                continue;
            }

            $query = "BEGIN SP_ELIMINAR_CARRITO(:ID_USUARIO, :ID_PRODUCTO, :ID_REFERENCIA); END;";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_REFERENCIA', $idReferencia, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = $this->oracleErrorMessage($stmt);
                oci_free_statement($stmt);
                throw new Exception($error);
            }

            oci_free_statement($stmt);
        }
    }

    private function itemsBaseQuery(bool $soloSeleccionados = false, bool $forUpdate = false): string {
        $whereSeleccion = $soloSeleccionados ? "AND NVL(vc.SELECCIONADO, 0) = 1" : "";

        return "SELECT vc.ID_CARRITO,
                       vc.ID_DETALLE,
                       vc.ID_PRODUCTO,
                       vc.ID_REFERENCIA,
                       vc.CANTIDAD,
                       NVL(vc.SELECCIONADO, 0) AS SELECCIONADO,
                       vc.NOMBRE,
                       vc.CODIGO,
                       vc.DESCRIPCION,
                       vc.PRECIO,
                       vc.ID_CATEGORIA,
                       vc.CATEGORIA_NOMBRE,
                       vc.NUMERO_REFERENCIA,
                       vc.MARCA,
                       vc.FABRICANTE,
                       NVL(vc.STOCK_P, 0) AS STOCK_P,
                       vc.SUBTOTAL,
                       vc.IMAGEN
                FROM V_CARRITO_USUARIO vc
                WHERE vc.ID_USUARIO = :ID_USUARIO
                $whereSeleccion
                ORDER BY vc.NOMBRE, vc.NUMERO_REFERENCIA";
    }

    private function itemsFallbackQuery(bool $soloSeleccionados = false): string {
        $whereSeleccion = $soloSeleccionados ? "AND NVL(dc.SELECCIONADO, 0) = 1" : "";

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
                       p.PRECIO * dc.CANTIDAD AS SUBTOTAL,
                       img.IMAGEN
                FROM CARRITO c
                INNER JOIN DETALLE_CARRITO dc ON dc.ID_CARRITO = c.ID_CARRITO
                INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = dc.ID_PRODUCTO
                LEFT JOIN REFERENCIA_PRODUCTO r ON r.ID_REFERENCIA = dc.ID_REFERENCIA
                LEFT JOIN CATEGORIA_PRODUCTO cp ON cp.ID_CATEGORIA = p.ID_CATEGORIA
                LEFT JOIN (
                    SELECT ID_REFERENCIA, SUM(STOCK_P) AS STOCK_P
                    FROM (
                        SELECT ID_REFERENCIA, NVL(STOCK_P, 0) AS STOCK_P
                        FROM COMPATIBILIDAD_VEHICULO
                        UNION ALL
                        SELECT ID_REFERENCIA, NVL(STOCK_P, 0) AS STOCK_P
                        FROM COMPATIBILIDAD_MAQUINARIA
                    )
                    GROUP BY ID_REFERENCIA
                ) stk ON stk.ID_REFERENCIA = dc.ID_REFERENCIA
                LEFT JOIN (
                    SELECT ID_PRODUCTO,
                           MIN(URL) KEEP (DENSE_RANK FIRST ORDER BY NVL(ORDEN, 999999), ID_IMAGEN) AS IMAGEN
                    FROM PRODUCTO_IMAGEN
                    GROUP BY ID_PRODUCTO
                ) img ON img.ID_PRODUCTO = p.ID_PRODUCTO
                WHERE c.ID_USUARIO = :ID_USUARIO
                $whereSeleccion
                ORDER BY p.NOMBRE, r.NUMERO_REFERENCIA";
    }

    private function obtenerItemsDesdeQuery(string $query, int $idUsuario, bool $tx = false): array {
        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        $mode = $tx ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS;
        if (!@oci_execute($stmt, $mode)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $items[] = $this->normalizarItem($row);
        }
        oci_free_statement($stmt);

        return $items;
    }

    public function obtenerItemsCheckoutTx($idUsuario): array {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        try {
            return $this->obtenerItemsDesdeQuery($this->itemsBaseQuery(true, false), $idUsuario, true);
        } catch (Exception $e) {
            if (!$this->esObjetoOracleNoVisible($e->getMessage())) {
                throw $e;
            }
            return $this->obtenerItemsDesdeQuery($this->itemsFallbackQuery(true), $idUsuario, true);
        }
    }

    public function obtenerItemsDetallados($idUsuario, bool $soloSeleccionados = false) {
        return $this->obtenerItemsVisualizacion($idUsuario, $soloSeleccionados);
    }

    public function obtenerItemsVisualizacion($idUsuario, bool $soloSeleccionados = false) {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        try {
            return $this->obtenerItemsDesdeQuery($this->itemsBaseQuery($soloSeleccionados, false), $idUsuario);
        } catch (Exception $e) {
            if (!$this->esObjetoOracleNoVisible($e->getMessage())) {
                throw $e;
            }
            return $this->obtenerItemsDesdeQuery($this->itemsFallbackQuery($soloSeleccionados), $idUsuario);
        }
    }

    public function obtenerMapaCarritoUsuario($idUsuario) {
        [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
        $query = "SELECT ID_PRODUCTO,
                         ID_REFERENCIA,
                         CANTIDAD
                  FROM V_CARRITO_USUARIO
                  WHERE ID_USUARIO = :ID_USUARIO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            if (!$this->esObjetoOracleNoVisible($message)) {
                throw new Exception($message);
            }

            $query = "SELECT dc.ID_PRODUCTO,
                             dc.ID_REFERENCIA,
                             dc.CANTIDAD
                      FROM CARRITO c
                      INNER JOIN DETALLE_CARRITO dc ON dc.ID_CARRITO = c.ID_CARRITO
                      WHERE c.ID_USUARIO = :ID_USUARIO";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
            oci_execute($stmt);
        }

        $mapa = [];
        while ($item = oci_fetch_assoc($stmt)) {
            $key = (int) ($item['ID_REFERENCIA'] ?? 0);
            if ($key <= 0) {
                $key = (int) ($item['ID_PRODUCTO'] ?? 0);
            }
            if ($key > 0) {
                $mapa[$key] = (int) $item['CANTIDAD'];
            }
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
        $query = "SELECT NVL(SUM(CANTIDAD), 0) AS TOTAL_ITEMS,
                         NVL(SUM(SUBTOTAL), 0) AS TOTAL_PAGAR
                  FROM V_CARRITO_USUARIO
                  WHERE ID_USUARIO = :ID_USUARIO
                    AND NVL(SELECCIONADO, 0) = 1";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            if (!$this->esObjetoOracleNoVisible($message)) {
                throw new Exception($message);
            }

            $query = "SELECT NVL(SUM(dc.CANTIDAD), 0) AS TOTAL_ITEMS,
                             NVL(SUM(p.PRECIO * dc.CANTIDAD), 0) AS TOTAL_PAGAR
                      FROM CARRITO c
                      INNER JOIN DETALLE_CARRITO dc ON dc.ID_CARRITO = c.ID_CARRITO
                      INNER JOIN PRODUCTO p ON p.ID_PRODUCTO = dc.ID_PRODUCTO
                      WHERE c.ID_USUARIO = :ID_USUARIO
                        AND NVL(dc.SELECCIONADO, 0) = 1";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $message = $this->oracleErrorMessage($stmt);
                oci_free_statement($stmt);
                throw new Exception($message);
            }
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
        // Optimizamos: consultamos directamente DETALLE_CARRITO y CARRITO para evitar el JOIN con PRODUCTO de la vista
        $query = "SELECT NVL(SUM(dc.CANTIDAD), 0) AS TOTAL_ITEMS
                  FROM CARRITO c
                  JOIN DETALLE_CARRITO dc ON dc.ID_CARRITO = c.ID_CARRITO
                  WHERE c.ID_USUARIO = :ID_USUARIO";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception($this->oracleErrorMessage());
        }

        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($error);
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
