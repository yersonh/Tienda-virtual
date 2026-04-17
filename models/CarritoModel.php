<?php

class CarritoModel {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function obtenerIdCarritoUsuario($idUsuario) {
        $query = "SELECT id_carrito FROM carrito WHERE id_usuario = :id_usuario LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (int) $row['id_carrito'] : null;
    }

    public function obtenerOCrearCarritoUsuario($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if ($idCarrito) {
            return $idCarrito;
        }

        $query = "INSERT INTO carrito (id_usuario) VALUES (:id_usuario) RETURNING id_carrito";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_usuario' => $idUsuario]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['id_carrito'] ?? 0);
    }

    public function agregarProducto($idUsuario, $idProducto, $cantidad) {
        $idCarrito = $this->obtenerOCrearCarritoUsuario($idUsuario);

        $query = "SELECT id_detalle, cantidad
                  FROM detalle_carrito
                  WHERE id_carrito = :id_carrito AND id_producto = :id_producto
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id_carrito' => $idCarrito,
            ':id_producto' => $idProducto
        ]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($detalle) {
            $query = "UPDATE detalle_carrito
                      SET cantidad = cantidad + :cantidad
                      WHERE id_detalle = :id_detalle";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':cantidad' => $cantidad,
                ':id_detalle' => $detalle['id_detalle']
            ]);
        } else {
            $query = "INSERT INTO detalle_carrito (id_carrito, id_producto, cantidad)
                      VALUES (:id_carrito, :id_producto, :cantidad)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id_carrito' => $idCarrito,
                ':id_producto' => $idProducto,
                ':cantidad' => $cantidad
            ]);
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
            return;
        }

        $query = "UPDATE detalle_carrito
                  SET cantidad = :cantidad
                  WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':cantidad' => $cantidad,
            ':id_carrito' => $idCarrito,
            ':id_producto' => $idProducto
        ]);
    }

    public function eliminarProducto($idUsuario, $idProducto) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return;
        }

        $query = "DELETE FROM detalle_carrito
                  WHERE id_carrito = :id_carrito AND id_producto = :id_producto";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':id_carrito' => $idCarrito,
            ':id_producto' => $idProducto
        ]);
    }

    public function vaciarCarrito($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return;
        }

        $query = "DELETE FROM detalle_carrito WHERE id_carrito = :id_carrito";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_carrito' => $idCarrito]);
    }

    public function obtenerMapaCarritoUsuario($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return [];
        }

        $query = "SELECT id_producto, cantidad
                  FROM detalle_carrito
                  WHERE id_carrito = :id_carrito";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_carrito' => $idCarrito]);

        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $mapa[(int) $item['id_producto']] = (int) $item['cantidad'];
        }

        return $mapa;
    }

    public function obtenerItemsDetallados($idUsuario) {
        $idCarrito = $this->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return [];
        }

        $query = "SELECT
                    p.*,
                    c.nombre AS categoria_nombre,
                    pi.url AS imagen,
                    dc.cantidad
                  FROM detalle_carrito dc
                  INNER JOIN producto p ON p.id_producto = dc.id_producto
                  INNER JOIN categoria_producto c ON c.id_categoria = p.id_categoria
                  LEFT JOIN producto_imagen pi ON pi.id_producto = p.id_producto AND pi.orden = 0
                  WHERE dc.id_carrito = :id_carrito
                  ORDER BY p.nombre";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id_carrito' => $idCarrito]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
