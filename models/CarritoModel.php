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

    private function validarIdsYCantidad($idUsuario, $idProducto = null, $cantidad = 1): array {
        $idUsuario = (int) $idUsuario;
        $idProducto = $idProducto === null ? null : (int) $idProducto;
        $cantidad = (int) $cantidad;

        if ($idUsuario <= 0) {
            throw new Exception('Usuario inválido');
        }

        if ($idProducto !== null && $idProducto <= 0) {
            throw new Exception('Producto inválido');
        }

        if ($cantidad <= 0) {
            throw new Exception('La cantidad debe ser mayor a 0');
        }

        return [$idUsuario, $idProducto, $cantidad];
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

    private function agregarDetalleEnCarritoTx($idCarrito, $idProducto, $cantidad): void {
        $query = "SELECT ID_DETALLE, CANTIDAD
                  FROM DETALLE_CARRITO
                  WHERE ID_CARRITO = :ID_CARRITO
                  AND ID_PRODUCTO = :ID_PRODUCTO
                  FOR UPDATE";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            throw new Exception($this->oracleErrorMessage($stmt));
        }

        $detalle = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($detalle) {
            $query = "UPDATE DETALLE_CARRITO
                      SET CANTIDAD = CANTIDAD + :CANTIDAD
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

        $query = "INSERT INTO DETALLE_CARRITO (ID_CARRITO, ID_PRODUCTO, CANTIDAD)
                  VALUES (:ID_CARRITO, :ID_PRODUCTO, :CANTIDAD)";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);

            if (str_contains($message, 'ORA-00001')) {
                $query = "UPDATE DETALLE_CARRITO
                          SET CANTIDAD = CANTIDAD + :CANTIDAD
                          WHERE ID_CARRITO = :ID_CARRITO
                          AND ID_PRODUCTO = :ID_PRODUCTO";

                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);
                oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
                oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);

                if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                    throw new Exception($this->oracleErrorMessage($stmt));
                }

                oci_free_statement($stmt);
                return;
            }

            throw new Exception($message);
        }

        oci_free_statement($stmt);
    }

    public function agregarAlCarrito($idUsuario, $idProducto, $cantidad) {
        try {
            [$idUsuario, $idProducto, $cantidad] = $this->validarIdsYCantidad($idUsuario, $idProducto, $cantidad);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);
            $this->agregarDetalleEnCarritoTx($idCarrito, $idProducto, $cantidad);
            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function eliminarDelCarrito($idUsuario, $idProducto) {
        try {
            [$idUsuario, $idProducto] = $this->validarIdsYCantidad($idUsuario, $idProducto);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);

            $query = "SELECT ID_DETALLE, CANTIDAD
                      FROM DETALLE_CARRITO
                      WHERE ID_CARRITO = :ID_CARRITO
                      AND ID_PRODUCTO = :ID_PRODUCTO
                      FOR UPDATE";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }

            $detalle = oci_fetch_assoc($stmt);
            oci_free_statement($stmt);

            if (!$detalle) {
                oci_commit($this->conn);
                return $this->success();
            }

            if ((int) $detalle['CANTIDAD'] > 1) {
                $query = "UPDATE DETALLE_CARRITO
                          SET CANTIDAD = CANTIDAD - 1
                          WHERE ID_DETALLE = :ID_DETALLE";
            } else {
                $query = "DELETE FROM DETALLE_CARRITO
                          WHERE ID_DETALLE = :ID_DETALLE";
            }

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_DETALLE', $detalle['ID_DETALLE'], -1, SQLT_INT);

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

    public function fusionarCarritoSesion($idUsuario, $carritoSesion) {
        if (empty($carritoSesion) || !is_array($carritoSesion)) {
            return $this->success();
        }

        try {
            [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);

            foreach ($carritoSesion as $idProducto => $cantidad) {
                $idProducto = (int) $idProducto;
                $cantidad = (int) $cantidad;

                if ($idProducto <= 0 || $cantidad <= 0) {
                    continue;
                }

                $this->agregarDetalleEnCarritoTx($idCarrito, $idProducto, $cantidad);
            }

            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function agregarProducto($idUsuario, $idProducto, $cantidad) {
        return $this->agregarAlCarrito($idUsuario, $idProducto, $cantidad);
    }

    public function eliminarProducto($idUsuario, $idProducto) {
        return $this->eliminarDelCarrito($idUsuario, $idProducto);
    }

    public function fusionarCarritoInvitado($idUsuario, $carritoInvitado) {
        return $this->fusionarCarritoSesion($idUsuario, $carritoInvitado);
    }

    public function actualizarCantidad($idUsuario, $idProducto, $cantidad) {
        try {
            [$idUsuario, $idProducto, $cantidad] = $this->validarIdsYCantidad($idUsuario, $idProducto, $cantidad);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);

            $query = "UPDATE DETALLE_CARRITO
                      SET CANTIDAD = :CANTIDAD
                      WHERE ID_CARRITO = :ID_CARRITO
                      AND ID_PRODUCTO = :ID_PRODUCTO";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':CANTIDAD', $cantidad, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':ID_PRODUCTO', $idProducto, -1, SQLT_INT);

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
            [$idUsuario] = $this->validarIdsYCantidad($idUsuario);
            $idCarrito = $this->obtenerOCrearCarritoUsuarioTx($idUsuario);

            $query = "DELETE FROM DETALLE_CARRITO
                      WHERE ID_CARRITO = :ID_CARRITO";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);

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

    public function obtenerMapaCarritoUsuario($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return [];
        }

        $query = "SELECT ID_PRODUCTO, CANTIDAD
                  FROM DETALLE_CARRITO
                  WHERE ID_CARRITO = :ID_CARRITO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
        oci_execute($stmt);

        $mapa = [];
        while ($item = oci_fetch_assoc($stmt)) {
            $mapa[(int) $item['ID_PRODUCTO']] = (int) $item['CANTIDAD'];
        }
        oci_free_statement($stmt);

        return $mapa;
    }

    public function obtenerItemsDetallados($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return [];
        }

        $query = "SELECT ID_CARRITO,
                         ID_PRODUCTO,
                         NOMBRE,
                         CODIGO,
                         DESCRIPCION,
                         PRECIO,
                         STOCK_P,
                         ID_CATEGORIA,
                         CATEGORIA_NOMBRE,
                         IMAGEN,
                         CANTIDAD
                  FROM V_CARRITO_COMPLETO
                  WHERE ID_CARRITO = :ID_CARRITO
                  ORDER BY NOMBRE";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_CARRITO', $idCarrito, -1, SQLT_INT);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerResumenCarrito($idUsuario) {
        $query = "SELECT ID_USUARIO,
                         NOMBRES,
                         APELLIDOS,
                         FN_TOTAL_ITEMS_CARRITO(ID_USUARIO) AS TOTAL_ITEMS,
                         FN_TOTAL_CARRITO(ID_USUARIO) AS TOTAL_PAGAR
                  FROM V_USUARIO_COMPLETO
                  WHERE ID_USUARIO = :ID_USUARIO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function obtenerTotalCarrito($idUsuario): float {
        $query = "SELECT FN_TOTAL_CARRITO(:ID_USUARIO) AS TOTAL_PAGAR FROM DUAL";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (float) ($row['TOTAL_PAGAR'] ?? 0);
    }

    public function obtenerTotalItemsCarrito($idUsuario): int {
        $query = "SELECT FN_TOTAL_ITEMS_CARRITO(:ID_USUARIO) AS TOTAL_ITEMS FROM DUAL";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ID_USUARIO', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL_ITEMS'] ?? 0);
    }
}
