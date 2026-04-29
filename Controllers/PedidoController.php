<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/DireccionPedidoModel.php';

class PedidoController {

    private $conn;
    private $carritoModel;
    private $direccionPedidoModel;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->carritoModel = new CarritoModel($this->conn);
        $this->direccionPedidoModel = new DireccionPedidoModel($this->conn);
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

    private function getUsuarioId(): int {
        return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    }

    private function isAjaxRequest(): bool {
        return (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch') ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );
    }

    private function calcularTotalCarrito(array $carrito): float {
        $ids = array_values(array_filter(array_map('intval', array_keys($carrito)), fn($id) => $id > 0));
        if (empty($ids)) {
            return 0;
        }

        $placeholders = [];
        $binds = [];
        foreach ($ids as $index => $idProducto) {
            $placeholder = ':id' . $index;
            $placeholders[] = $placeholder;
            $binds[$placeholder] = $idProducto;
        }

        $query = "SELECT ID_PRODUCTO, PRECIO
                  FROM PRODUCTO
                  WHERE ID_PRODUCTO IN (" . implode(', ', $placeholders) . ")";

        $stmt = oci_parse($this->conn, $query);
        foreach ($binds as $placeholder => &$value) {
            oci_bind_by_name($stmt, $placeholder, $value, -1, SQLT_INT);
        }
        unset($value);

        oci_execute($stmt);

        $precios = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $precios[(int) $row['ID_PRODUCTO']] = (float) $row['PRECIO'];
        }
        oci_free_statement($stmt);

        $total = 0;
        foreach ($carrito as $idProducto => $cantidad) {
            $idProducto = (int) $idProducto;
            if (!isset($precios[$idProducto])) {
                continue;
            }

            $total += $precios[$idProducto] * (int) $cantidad;
        }

        return $total;
    }

    private function obtenerColumnasTabla(string $tabla): array {
        $query = "SELECT COLUMN_NAME, NULLABLE, IDENTITY_COLUMN
                  FROM USER_TAB_COLUMNS
                  WHERE TABLE_NAME = :tabla
                  ORDER BY COLUMN_ID";

        $stmt = oci_parse($this->conn, $query);
        $tabla = strtoupper($tabla);
        oci_bind_by_name($stmt, ':tabla', $tabla);
        oci_execute($stmt);

        $columnas = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $columnas[strtolower($row['COLUMN_NAME'])] = [
                'name' => $row['COLUMN_NAME'],
                'nullable' => $row['NULLABLE'],
                'identity' => $row['IDENTITY_COLUMN'] ?? 'NO'
            ];
        }

        oci_free_statement($stmt);

        return $columnas;
    }

    private function crearVenta(int $idUsuario, float $total): int {
        $columnas = $this->obtenerColumnasTabla('VENTA');
        $insertColumns = [];
        $valueExpressions = [];
        $binds = [];

        foreach ($columnas as $lowerName => $meta) {
            if ($lowerName === 'id_venta' || strtoupper($meta['identity']) === 'YES') {
                continue;
            }

            if (in_array($lowerName, ['id_usuario', 'id_cliente'], true)) {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = ':' . $lowerName;
                $binds[':' . $lowerName] = ['value' => $idUsuario, 'type' => SQLT_INT];
                continue;
            }

            if (in_array($lowerName, ['total', 'total_venta', 'total_pagar', 'valor_total', 'monto_total'], true)) {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = ':' . $lowerName;
                $binds[':' . $lowerName] = ['value' => $total, 'type' => SQLT_FLT];
                continue;
            }

            if (in_array($lowerName, ['fecha', 'fecha_venta', 'fecha_creacion', 'created_at'], true)) {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = 'SYSDATE';
                continue;
            }

            if ($lowerName === 'id_estado') {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = ':id_estado_venta';
                $binds[':id_estado_venta'] = ['value' => 1, 'type' => SQLT_INT];
                continue;
            }

            if ($lowerName === 'estado') {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = ':estado_venta';
                $binds[':estado_venta'] = ['value' => 'PENDIENTE', 'type' => SQLT_CHR];
                continue;
            }

            if ($meta['nullable'] === 'N') {
                throw new Exception('La tabla VENTA tiene una columna obligatoria no mapeada: ' . $meta['name']);
            }
        }

        if (empty($insertColumns)) {
            $query = "INSERT INTO VENTA (ID_VENTA)
                      VALUES (DEFAULT)
                      RETURNING ID_VENTA INTO :id_venta";
        } else {
            $query = "INSERT INTO VENTA (" . implode(', ', $insertColumns) . ")
                      VALUES (" . implode(', ', $valueExpressions) . ")
                      RETURNING ID_VENTA INTO :id_venta";
        }

        $stmt = oci_parse($this->conn, $query);
        $idVenta = null;

        foreach ($binds as $placeholder => &$bind) {
            oci_bind_by_name($stmt, $placeholder, $bind['value'], -1, $bind['type']);
        }
        unset($bind);

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo crear la venta');
        }

        oci_free_statement($stmt);

        return (int) $idVenta;
    }

    private function crearPedido(int $idVenta, int $idDireccion, int $estado = 1): int {
        $query = "INSERT INTO PEDIDO (ID_VENTA, ID_ESTADO, ID_DIRECCION_PEDIDO)
                  VALUES (:id_venta, :estado, :id_direccion)
                  RETURNING ID_PEDIDO INTO :id_pedido";

        $stmt = oci_parse($this->conn, $query);
        $idPedido = null;

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':estado', $estado, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_direccion', $idDireccion, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo crear el pedido');
        }

        oci_free_statement($stmt);

        return (int) $idPedido;
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

    public function checkout() {
        $this->ensureSession();

        $pedidoConfirmado = $_SESSION['pedido_confirmado'] ?? null;
        if ($pedidoConfirmado) {
            unset($_SESSION['pedido_confirmado']);
            $direcciones = [];
            $total = (float) ($pedidoConfirmado['total'] ?? 0);
            require_once __DIR__ . '/../views/checkout.php';
            return;
        }

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para continuar la compra';
            header("Location: index.php?action=login");
            exit();
        }

        $carrito = $this->obtenerCarritoSesion();
        if (empty($carrito)) {
            $_SESSION['error'] = 'Tu carrito esta vacio';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        $direcciones = $this->direccionPedidoModel->obtenerDirecciones($idUsuario);
        $total = $this->calcularTotalCarrito($carrito);

        require_once __DIR__ . '/../views/checkout.php';
    }

    public function guardarDireccion() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            if ($this->isAjaxRequest()) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Debes iniciar sesion para guardar una direccion']);
                exit();
            }

            $_SESSION['error'] = 'Debes iniciar sesion para guardar una direccion';
            header("Location: index.php?action=login");
            exit();
        }

        $data = [
            'id_usuario' => $idUsuario,
            'nombre_receptor' => $_POST['nombre_receptor'] ?? '',
            'apellido_receptor' => $_POST['apellido_receptor'] ?? '',
            'direccion_envio' => $_POST['direccion_envio'] ?? '',
            'ciudad' => $_POST['ciudad'] ?? '',
            'barrio' => $_POST['barrio'] ?? '',
            'telefono_receptor' => $_POST['telefono_receptor'] ?? '',
            'telefono_alterno' => $_POST['telefono_alterno'] ?? '',
            'es_predeterminada' => $_POST['es_predeterminada'] ?? 0
        ];

        $resultado = $this->direccionPedidoModel->guardarDireccion($data);
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            if (!$resultado['success']) {
                http_response_code(422);
            }

            echo json_encode($resultado);
            exit();
        }

        if (!$resultado['success']) {
            $_SESSION['error'] = $resultado['message'] ?? 'No se pudo guardar la direccion';
        } else {
            $_SESSION['success'] = 'Direccion guardada correctamente';
        }

        header("Location: index.php?action=checkout");
        exit();
    }

    public function procesarPedido() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para finalizar la compra';
            header("Location: index.php?action=login");
            exit();
        }

        $idDireccion = (int) ($_POST['id_direccion'] ?? 0);
        if ($idDireccion <= 0) {
            $_SESSION['error'] = 'Selecciona una direccion de envio';
            header("Location: index.php?action=checkout");
            exit();
        }

        $direccion = $this->direccionPedidoModel->obtenerDireccionPorId($idDireccion);
        if (!$direccion || (int) $direccion['id_usuario'] !== $idUsuario) {
            $_SESSION['error'] = 'La direccion seleccionada no es valida';
            header("Location: index.php?action=checkout");
            exit();
        }

        $carrito = $this->obtenerCarritoSesion();
        if (empty($carrito)) {
            $_SESSION['error'] = 'Tu carrito esta vacio';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        try {
            $total = $this->calcularTotalCarrito($carrito);
            $idVenta = $this->crearVenta($idUsuario, $total);
            $idPedido = $this->crearPedido($idVenta, $idDireccion, 1);

            $this->carritoModel->vaciarCarrito($idUsuario);
            unset($_SESSION['carrito'], $_SESSION['carrito_mapa_cache']);
            $_SESSION['carrito_count'] = 0;
            $_SESSION['pedido_confirmado'] = [
                'id_pedido' => $idPedido,
                'id_venta' => $idVenta,
                'total' => $total
            ];

            oci_commit($this->conn);
            header("Location: index.php?action=checkout");
            exit();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $_SESSION['error'] = 'No se pudo procesar el pedido. Verifica la informacion e intenta de nuevo.';
            header("Location: index.php?action=checkout");
            exit();
        }
    }

    public function confirmacion() {
        $this->ensureSession();

        $pedido = $_SESSION['pedido_confirmado'] ?? null;
        if (!$pedido) {
            header("Location: index.php?action=tienda");
            exit();
        }

        require_once __DIR__ . '/../views/pedidos/confirmacion.php';
    }
}
