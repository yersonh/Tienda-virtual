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

    private function failCartOperation($resultado) {
        return is_array($resultado)
            && isset($resultado['success'])
            && $resultado['success'] === false;
    }

    private function respondCartError($resultado, $fallback = 'No se pudo actualizar el carrito') {
        $message = is_array($resultado) && !empty($resultado['message'])
            ? $resultado['message']
            : $fallback;

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(['ok' => false, 'message' => $message]);
            exit();
        }

        $_SESSION['error'] = $message;
        header("Location: index.php?action=verCarrito");
        exit();
    }

    private function syncSessionCartFromSource() {
        $idUsuario = $this->getUsuarioId();
        if ($idUsuario > 0) {
            unset($_SESSION['carrito']);
            return $this->carritoModel->obtenerMapaCarritoUsuario($idUsuario);
        }

        if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        return $_SESSION['carrito'];
    }

    private function getDetailedItems() {
        $idUsuario = $this->getUsuarioId();
        $ajustoCantidades = false;

        if ($idUsuario > 0) {
            return $this->carritoModel->obtenerItemsVisualizacion($idUsuario);
        }

        $carrito = $this->syncSessionCartFromSource();

        $items = [];
        if (!empty($carrito)) {
            $productos = $this->productoModel->obtenerPorIds(array_keys($carrito));
            foreach ($productos as $producto) {
                $producto['cantidad'] = (int) ($carrito[(int) $producto['id_producto']] ?? 1);
                $items[] = $producto;
            }
        }

        foreach ($items as &$item) {
            $item['cantidad'] = max(1, (int) ($item['cantidad'] ?? 1));
            $stock = max(0, (int) ($item['stock_p'] ?? 0));

            if ($stock === 0) {
                if ($idUsuario > 0) {
                    $this->carritoModel->eliminarProducto($idUsuario, (int) $item['id_producto']);
                } else {
                    unset($_SESSION['carrito'][(int) $item['id_producto']]);
                }
                $item['cantidad'] = 0;
                $ajustoCantidades = true;
                continue;
            }

            if ($item['cantidad'] > $stock) {
                $item['cantidad'] = $stock;
                if ($idUsuario > 0) {
                    $this->carritoModel->actualizarCantidad($idUsuario, (int) $item['id_producto'], $stock);
                } else {
                    $_SESSION['carrito'][(int) $item['id_producto']] = $stock;
                }
                $ajustoCantidades = true;
            }

            $item['total_linea'] = $item['cantidad'] * (float) $item['precio'];
        }
        unset($item);

        $items = array_values(array_filter($items, function($item) {
            return (int) ($item['cantidad'] ?? 0) > 0;
        }));

        return $items;
    }

    private function buildCartResponse($idProducto = null) {
        $cantidad = 0;
        $lineaTotal = 0;
        $subtotal = 0;
        $stockDisponible = 0;
        $total = 0;
        $productoRespuesta = $idProducto !== null ? $this->productoModel->obtenerPorId((int) $idProducto) : null;
        if ($productoRespuesta) {
            $stockDisponible = (int) ($productoRespuesta['stock_p'] ?? 0);
        }

        $this->syncSessionCartFromSource();
        foreach ($this->getDetailedItems() as $item) {
            $subtotal += $item['total_linea'];
            $total += (int) $item['cantidad'];

            if ($idProducto !== null && (int) $item['id_producto'] === (int) $idProducto) {
                $cantidad = (int) $item['cantidad'];
                $lineaTotal = (float) $item['total_linea'];
                $stockDisponible = $stockDisponible ?: (int) ($item['stock_p'] ?? 0);
            }
        }

        return [
            'ok' => true,
            'total' => $total,
            'subtotal' => $subtotal,
            'cantidad' => $cantidad,
            'linea_total' => $lineaTotal,
            'stock' => $stockDisponible
        ];
    }

    public function agregar() {
        $this->ensureSession();

        $id = (int) ($_POST['id_producto'] ?? 0);
        $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
        $idUsuario = $this->getUsuarioId();
        $producto = $this->productoModel->obtenerPorId($id);

        if ($id <= 0 || !$producto) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['ok' => false, 'message' => 'Producto invalido']);
                exit();
            }

            header("Location: index.php?action=tienda");
            exit();
        }

        $stockDisponible = max(0, (int) ($producto['stock_p'] ?? 0));
        $carritoActual = $this->syncSessionCartFromSource();
        $cantidadActual = (int) ($carritoActual[$id] ?? 0);
        $cantidadFinal = min($stockDisponible, $cantidadActual + $cantidad);
        $cantidadAgregar = max(0, $cantidadFinal - $cantidadActual);

        if ($cantidadAgregar <= 0) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(422);
                echo json_encode([
                    'ok' => false,
                    'message' => 'No hay stock disponible suficiente',
                    'cantidad' => $cantidadActual,
                    'stock' => $stockDisponible,
                    'total' => array_sum($carritoActual)
                ]);
                exit();
            }

            header("Location: index.php?action=verCarrito");
            exit();
        }

        if ($idUsuario > 0) {
            $resultado = $this->carritoModel->agregarProducto($idUsuario, $id, $cantidadAgregar);
            if ($this->failCartOperation($resultado)) {
                $this->respondCartError($resultado, 'No se pudo agregar el producto al carrito');
            }
        } else {
            if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
                $_SESSION['carrito'] = [];
            }
            $_SESSION['carrito'][$id] = $cantidadFinal;
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

        $items = $this->getDetailedItems();
        $resumenCarrito = null;
        $subtotal = $resumenCarrito['total_pagar'] ?? array_reduce($items, function($carry, $item) {
            return $carry + (float) $item['total_linea'];
        }, 0);

        require_once __DIR__ . '/../views/tienda/carrito.php';
    }

    public function actualizar() {
        $this->ensureSession();

        $id = (int) ($_POST['id_producto'] ?? 0);
        $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));
        $idUsuario = $this->getUsuarioId();
        $producto = $this->productoModel->obtenerPorId($id);

        if (!$producto) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => 'Producto no encontrado']);
                exit();
            }

            header("Location: index.php?action=verCarrito");
            exit();
        }

        $stockDisponible = max(0, (int) ($producto['stock_p'] ?? 0));
        if ($stockDisponible <= 0) {
            if ($idUsuario > 0) {
                $this->carritoModel->eliminarProducto($idUsuario, $id);
            } else {
                unset($_SESSION['carrito'][$id]);
            }
            $cantidad = 0;
        } else {
            $cantidad = min($cantidad, $stockDisponible);
        }

        if ($cantidad <= 0) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode($this->buildCartResponse($id));
                exit();
            }

            header("Location: index.php?action=verCarrito");
            exit();
        }

        if ($idUsuario > 0) {
            $resultado = $this->carritoModel->actualizarCantidad($idUsuario, $id, $cantidad);
            if ($this->failCartOperation($resultado)) {
                $this->respondCartError($resultado, 'No se pudo actualizar la cantidad');
            }
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
            $resultado = $this->carritoModel->eliminarProducto($idUsuario, $id);
            if ($this->failCartOperation($resultado)) {
                $this->respondCartError($resultado, 'No se pudo eliminar el producto del carrito');
            }
        } elseif (isset($_SESSION['carrito'][$id])) {
            unset($_SESSION['carrito'][$id]);
        }

        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode($this->buildCartResponse($id));
            exit();
        }

        header("Location: index.php?action=verCarrito");
        exit();
    }

    public function vaciar() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario > 0) {
            $resultado = $this->carritoModel->vaciarCarrito($idUsuario);
            if ($this->failCartOperation($resultado)) {
                $this->respondCartError($resultado, 'No se pudo vaciar el carrito');
            }
        } else {
            $_SESSION['carrito'] = [];
        }

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
