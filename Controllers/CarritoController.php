<?php
require_once __DIR__ . '/../models/ProductoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../config/database.php';

class CarritoController {

    private $productoModel;
    private $carritoModel;

    public function __construct() {
        $pdo = Database::getConnection();
        $this->productoModel = new ProductoModel($pdo);
        $this->carritoModel = new CarritoModel($pdo);
    }

    private function ensureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isAjaxRequest() {
        return (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch') ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );
    }

    private function getUsuarioId() {
        return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    }

    private function syncSessionCartFromSource() {
        $idUsuario = $this->getUsuarioId();
        if ($idUsuario > 0) {
            $_SESSION['carrito'] = $this->carritoModel->obtenerMapaCarritoUsuario($idUsuario);
            return $_SESSION['carrito'];
        }

        if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        return $_SESSION['carrito'];
    }

    private function getDetailedItems() {
        $idUsuario = $this->getUsuarioId();
        if ($idUsuario > 0) {
            $items = $this->carritoModel->obtenerItemsDetallados($idUsuario);
        } else {
            $carrito = $_SESSION['carrito'] ?? [];
            $items = [];

            if (!empty($carrito)) {
                $productos = $this->productoModel->obtenerPorIds(array_keys($carrito));
                foreach ($productos as $producto) {
                    $producto['cantidad'] = (int) ($carrito[(int) $producto['id_producto']] ?? 1);
                    $items[] = $producto;
                }
            }
        }

        foreach ($items as &$item) {
            $item['cantidad'] = max(1, (int) ($item['cantidad'] ?? 1));
            $item['total_linea'] = $item['cantidad'] * (float) $item['precio'];
        }
        unset($item);

        return $items;
    }

    private function buildCartResponse($idProducto = null) {
        $carrito = $this->syncSessionCartFromSource();
        $cantidad = 0;
        $lineaTotal = 0;
        $subtotal = 0;

        foreach ($this->getDetailedItems() as $item) {
            $subtotal += $item['total_linea'];

            if ($idProducto !== null && (int) $item['id_producto'] === (int) $idProducto) {
                $cantidad = (int) $item['cantidad'];
                $lineaTotal = (float) $item['total_linea'];
            }
        }

        return [
            'ok' => true,
            'total' => array_sum($carrito),
            'subtotal' => $subtotal,
            'cantidad' => $cantidad,
            'linea_total' => $lineaTotal
        ];
    }

    public function agregar() {
        $this->ensureSession();

        $id = (int) ($_POST['id_producto'] ?? 0);
        $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
        $idUsuario = $this->getUsuarioId();

        if ($id <= 0) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => 'Producto invalido']);
                exit();
            }

            header("Location: index.php?action=tienda");
            exit();
        }

        if ($idUsuario > 0) {
            $this->carritoModel->agregarProducto($idUsuario, $id, $cantidad);
            $this->syncSessionCartFromSource();
        } else {
            if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }

            if (isset($_SESSION['carrito'][$id])) {
                $_SESSION['carrito'][$id] += $cantidad;
            } else {
                $_SESSION['carrito'][$id] = $cantidad;
            }
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($this->buildCartResponse($id));
            exit();
        }

        header("Location: index.php?action=verCarrito");
        exit();
    }

    public function ver() {
        $this->ensureSession();
        $this->syncSessionCartFromSource();

        $items = $this->getDetailedItems();
        $subtotal = array_reduce($items, function($carry, $item) {
            return $carry + (float) $item['total_linea'];
        }, 0);

        require_once __DIR__ . '/../views/tienda/carrito.php';
    }

    public function actualizar() {
        $this->ensureSession();

        $id = (int) ($_POST['id_producto'] ?? 0);
        $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
        $idUsuario = $this->getUsuarioId();

        if ($idUsuario > 0) {
            $this->carritoModel->actualizarCantidad($idUsuario, $id, $cantidad);
            $this->syncSessionCartFromSource();
        } else {
            if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }
            $_SESSION['carrito'][$id] = $cantidad;
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($this->buildCartResponse($id));
            exit();
        }

        header("Location: index.php?action=verCarrito");
        exit();
    }

    public function eliminar() {
        $this->ensureSession();

        $id = (int) ($_POST['id_producto'] ?? ($_GET['id'] ?? 0));
        $idUsuario = $this->getUsuarioId();

        if ($idUsuario > 0) {
            $this->carritoModel->eliminarProducto($idUsuario, $id);
            $this->syncSessionCartFromSource();
        } else {
            if (isset($_SESSION['carrito'][$id])) {
                unset($_SESSION['carrito'][$id]);
            }
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($this->buildCartResponse());
            exit();
        }

        header("Location: index.php?action=verCarrito");
        exit();
    }

    public function vaciar() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario > 0) {
            $this->carritoModel->vaciarCarrito($idUsuario);
        }
        $_SESSION['carrito'] = [];

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true,
                'total' => 0,
                'subtotal' => 0
            ]);
            exit();
        }

        header("Location: index.php?action=verCarrito");
        exit();
    }
}
