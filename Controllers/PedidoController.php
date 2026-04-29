<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CarritoModel.php';

class PedidoController {

    private $conn;
    private $carritoModel;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->carritoModel = new CarritoModel($this->conn);
    }

    private function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function obtenerCarritoSesion(): array {
        if (!empty($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
            return $_SESSION['carrito'];
        }

        if (!empty($_SESSION['carrito_mapa_cache']['data']) && is_array($_SESSION['carrito_mapa_cache']['data'])) {
            return $_SESSION['carrito_mapa_cache']['data'];
        }

        if (!empty($_SESSION['id_usuario'])) {
            $carrito = $this->carritoModel->obtenerMapaCarritoUsuario((int) $_SESSION['id_usuario']);
            $_SESSION['carrito_mapa_cache'] = [
                'expires' => time() + 30,
                'data' => $carrito
            ];
            return $carrito;
        }

        return [];
    }

    private function obtenerProductoResumen(int $idProducto): ?array {
        $query = "SELECT NOMBRE, PRECIO FROM PRODUCTO WHERE ID_PRODUCTO = :id";
        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id', $idProducto, -1, SQLT_INT);
        oci_execute($stmt);

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    public function resumen() {
        $this->ensureSession();

        $carrito = $this->obtenerCarritoSesion();
        $items = [];
        $total = 0;

        foreach ($carrito as $idProducto => $cantidad) {
            $idProducto = (int) $idProducto;
            $cantidad = (int) $cantidad;

            if ($idProducto <= 0 || $cantidad <= 0) {
                continue;
            }

            $producto = $this->obtenerProductoResumen($idProducto);
            if (!$producto) {
                continue;
            }

            $precio = (float) $producto['precio'];
            $subtotal = $precio * $cantidad;
            $total += $subtotal;

            $items[] = [
                'id_producto' => $idProducto,
                'nombre' => $producto['nombre'],
                'precio' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $subtotal
            ];
        }

        if (empty($items)) {
            $_SESSION['error'] = 'Tu carrito esta vacio';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        require_once __DIR__ . '/../views/pedidos/resumen.php';
    }
}
