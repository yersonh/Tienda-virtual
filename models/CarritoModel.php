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

    public function obtenerIdCarritoUsuario($idUsuario) {
        $query = "SELECT id_carrito FROM carrito WHERE id_usuario = :id_usuario FETCH FIRST 1 ROWS ONLY";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? (int) $row['ID_CARRITO'] : null;
    }

    public function obtenerOCrearCarritoUsuario($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if ($idCarrito) {
            return $idCarrito;
        }

        $query = "BEGIN PC_CREAR_CARRITO(:id_usuario); END;";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt)) {
            $message = $this->oracleErrorMessage($stmt);
            oci_free_statement($stmt);
            throw new Exception($message);
        }

        oci_free_statement($stmt);

        return $this->obtenerIdCarritoUsuario($idUsuario);
    }

    public function agregarProducto($idUsuario, $idProducto, $cantidad) {
        try {
            $idCarrito = $this->obtenerOCrearCarritoUsuario($idUsuario);
            if (!$idCarrito) {
                throw new Exception('No se pudo crear o recuperar el carrito del usuario');
            }

            $query = "SELECT id_carrito
                      FROM carrito
                      WHERE id_carrito = :id_carrito
                      FOR UPDATE";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }
            oci_free_statement($stmt);

            $query = "SELECT id_detalle, cantidad
                      FROM detalle_carrito
                      WHERE id_carrito = :id_carrito AND id_producto = :id_producto
                      FETCH FIRST 1 ROWS ONLY";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);
            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                throw new Exception($this->oracleErrorMessage($stmt));
            }
            $detalle = oci_fetch_assoc($stmt);
            oci_free_statement($stmt);

            if ($detalle) {
                $query = "UPDATE detalle_carrito
                          SET cantidad = cantidad + :cantidad
                          WHERE id_detalle = :id_detalle";
                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ':cantidad', $cantidad, -1, SQLT_INT);
                oci_bind_by_name($stmt, ':id_detalle', $detalle['ID_DETALLE'], -1, SQLT_INT);
                if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                    throw new Exception($this->oracleErrorMessage($stmt));
                }
                oci_free_statement($stmt);
            } else {
                $query = "INSERT INTO detalle_carrito (id_carrito, id_producto, cantidad)
                          VALUES (:id_carrito, :id_producto, :cantidad)";
                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
                oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);
                oci_bind_by_name($stmt, ':cantidad', $cantidad, -1, SQLT_INT);
                if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                    throw new Exception($this->oracleErrorMessage($stmt));
                }
                oci_free_statement($stmt);
            }

            oci_commit($this->conn);
            return $this->success();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            return $this->failure($e->getMessage());
        }
    }

    public function fusionarCarritoInvitado($idUsuario, $carritoInvitado) {
        if (empty($carritoInvitado) || !is_array($carritoInvitado)) {
            return;
        }

        foreach ($carritoInvitado as $idProducto => $cantidad) {
            $idProducto = (int) $idProducto;
            $cantidad = max(1, (int) $cantidad);

            if ($idProducto > 0) {
                $this->agregarProducto($idUsuario, $idProducto, $cantidad);
            }
        }
    }

    public function actualizarCantidad($idUsuario, $idProducto, $cantidad) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return $this->success();
        }

        try {
            $query = "UPDATE detalle_carrito
                      SET cantidad = :cantidad
                      WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':cantidad', $cantidad, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);
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

    public function eliminarProducto($idUsuario, $idProducto) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return $this->success();
        }

        try {
            $query = "DELETE FROM detalle_carrito
                      WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);
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
            $query = "BEGIN PC_VACIAR_CARRITO(:id_usuario); END;";
            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
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

        $query = "SELECT id_producto, cantidad
                  FROM v_carrito_completo
                  WHERE id_carrito = :id_carrito";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
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

        $query = "SELECT id_carrito,
                         id_producto,
                         nombre,
                         codigo,
                         descripcion,
                         precio,
                         stock_p,
                         id_categoria,
                         categoria_nombre,
                         imagen,
                         cantidad
                  FROM v_carrito_completo
                  WHERE id_carrito = :id_carrito
                  ORDER BY nombre";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
        oci_execute($stmt);

        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $results;
    }

    public function obtenerResumenCarrito($idUsuario) {
        $query = "SELECT
                    id_usuario,
                    nombres,
                    apellidos,
                    FN_TOTAL_ITEMS_CARRITO(id_usuario) AS total_items,
                    FN_TOTAL_CARRITO(id_usuario) AS total_pagar
                  FROM v_usuario_completo
                  WHERE id_usuario = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function obtenerTotalCarrito($idUsuario): float {
        $query = "SELECT FN_TOTAL_CARRITO(:id_usuario) AS total_pagar FROM dual";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (float) ($row['TOTAL_PAGAR'] ?? 0);
    }

    public function obtenerTotalItemsCarrito($idUsuario): int {
        $query = "SELECT FN_TOTAL_ITEMS_CARRITO(:id_usuario) AS total_items FROM dual";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return (int) ($row['TOTAL_ITEMS'] ?? 0);
    }
}
