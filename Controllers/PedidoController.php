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
            $_SESSION['carrito_count'] = array_sum($_SESSION['carrito']);
            return $_SESSION['carrito'];
        }

        if (
            !empty($_SESSION['carrito_mapa_cache']['data'])
            && is_array($_SESSION['carrito_mapa_cache']['data'])
            && (int) ($_SESSION['carrito_mapa_cache']['expires'] ?? 0) >= time()
        ) {
            $_SESSION['carrito_count'] = array_sum($_SESSION['carrito_mapa_cache']['data']);
            return $_SESSION['carrito_mapa_cache']['data'];
        }

        if (!empty($_SESSION['id_usuario'])) {
            $carrito = $this->carritoModel->obtenerMapaCarritoUsuario((int) $_SESSION['id_usuario']);
            $_SESSION['carrito_mapa_cache'] = [
                'expires' => time() + 30,
                'data' => $carrito
            ];
            $_SESSION['carrito_count'] = array_sum($carrito);
            return $carrito;
        }

        return [];
    }

    private function obtenerProductoResumen(int $idProducto): ?array {
        $productos = $this->obtenerProductosResumen([$idProducto]);
        return $productos[$idProducto] ?? null;
    }

    private function obtenerProductosResumen(array $idsProductos): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsProductos), fn($id) => $id > 0)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = [];
        $binds = [];
        foreach ($ids as $index => $idProducto) {
            $placeholder = ':id' . $index;
            $placeholders[] = $placeholder;
            $binds[$placeholder] = $idProducto;
        }

        $query = "SELECT ID_PRODUCTO, NOMBRE, PRECIO
                  FROM PRODUCTO
                  WHERE ID_PRODUCTO IN (" . implode(', ', $placeholders) . ")";

        $stmt = oci_parse($this->conn, $query);
        foreach ($binds as $placeholder => &$value) {
            oci_bind_by_name($stmt, $placeholder, $value, -1, SQLT_INT);
        }
        unset($value);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudieron consultar los productos');
        }

        $productos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $productos[(int) $row['ID_PRODUCTO']] = [
                'nombre' => $row['NOMBRE'],
                'precio' => (float) $row['PRECIO']
            ];
        }

        oci_free_statement($stmt);
        return $productos;
    }

    private function getUsuarioId(): int {
        return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    }

    private function obtenerPersonaUsuario(int $idUsuario): int {
        $idPersonaSesion = (int) ($_SESSION['usuario']['id_persona'] ?? 0);
        if ($idPersonaSesion > 0) {
            return $idPersonaSesion;
        }

        $query = "SELECT ID_PERSONA
                  FROM USUARIO
                  WHERE ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar la persona del usuario');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        $idPersona = (int) ($row['ID_PERSONA'] ?? 0);
        if ($idPersona <= 0) {
            throw new Exception('El usuario no tiene persona asociada');
        }

        $_SESSION['usuario']['id_persona'] = $idPersona;
        return $idPersona;
    }

    private function asegurarClientePorPersona(int $idPersona): int {
        $query = "SELECT ID_CLIENTE
                  FROM CLIENTE
                  WHERE ID_PERSONA = :id_persona";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_persona', $idPersona, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el cliente');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row && isset($row['ID_CLIENTE'])) {
            return (int) $row['ID_CLIENTE'];
        }

        $insert = "INSERT INTO CLIENTE (ID_CLIENTE, ID_PERSONA, FECHA_REGISTRO)
                   VALUES (SEQ_CLIENTE.NEXTVAL, :id_persona, SYSDATE)
                   RETURNING ID_CLIENTE INTO :id_cliente";

        $stmtInsert = oci_parse($this->conn, $insert);
        $idCliente = null;
        oci_bind_by_name($stmtInsert, ':id_persona', $idPersona, -1, SQLT_INT);
        oci_bind_by_name($stmtInsert, ':id_cliente', $idCliente, -1, SQLT_INT);

        if (!@oci_execute($stmtInsert, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmtInsert);
            oci_free_statement($stmtInsert);
            throw new Exception($error['message'] ?? 'No se pudo crear el cliente');
        }

        oci_free_statement($stmtInsert);

        return (int) $idCliente;
    }

    private function isAjaxRequest(): bool {
        return (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'fetch') ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );
    }

    private function calcularTotalCarrito(array $carrito): float {
        return $this->calcularTotalProductos($this->normalizarCarrito($carrito), $this->obtenerProductosResumen(array_keys($carrito)));
    }

    private function calcularTotalProductos(array $itemsPedido, array $productos): float {
        $total = 0;
        foreach ($itemsPedido as $idProducto => $cantidad) {
            $idProducto = (int) $idProducto;
            if (!isset($productos[$idProducto])) {
                continue;
            }

            $total += (float) $productos[$idProducto]['precio'] * (int) $cantidad;
        }

        return $total;
    }

    private function normalizarCarrito(array $carrito): array {
        $normalizado = [];
        foreach ($carrito as $idProducto => $cantidad) {
            $idProducto = (int) $idProducto;
            $cantidad = (int) $cantidad;
            if ($idProducto > 0 && $cantidad > 0) {
                $normalizado[$idProducto] = $cantidad;
            }
        }

        return $normalizado;
    }

    private function normalizarCiudadEnvio(string $ciudad): string {
        $ciudad = trim(function_exists('mb_strtolower') ? mb_strtolower($ciudad, 'UTF-8') : strtolower($ciudad));
        $sinAcentos = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $ciudad);
        if ($sinAcentos !== false) {
            $ciudad = strtolower($sinAcentos);
        }

        $ciudad = strtr($ciudad, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        return preg_replace('/\s+/', ' ', trim($ciudad));
    }

    private function tarifasEnvioPorCiudad(): array {
        return [
            'villavicencio' => 2000,
            'puerto lopez' => 8000,
            'granada' => 10000,
            'san jose del guaviare' => 14000,
            'yopal' => 14000,
            'bogota' => 10000,
            'soacha' => 10000,
            'chia' => 11000,
            'mosquera' => 11000,
            'facatativa' => 12000,
            'zipaquira' => 12000,
            'fusagasuga' => 12000,
            'girardot' => 13000,
            'tunja' => 14000,
            'duitama' => 14000,
            'sogamoso' => 15000,
            'ibague' => 15000,
            'neiva' => 16000,
            'arauca' => 18000,
            'puerto carreno' => 22000,
            'medellin' => 18000,
            'itagui' => 18000,
            'envigado' => 18000,
            'bello' => 18000,
            'rionegro' => 19000,
            'floridablanca' => 19000,
            'giron' => 19000,
            'bucaramanga' => 19000,
            'pereira' => 19000,
            'armenia' => 19000,
            'cartago' => 19000,
            'manizales' => 20000,
            'cali' => 20000,
            'palmira' => 20000,
            'tulua' => 21000,
            'popayan' => 22000,
            'buenaventura' => 23000,
            'pasto' => 24000,
            'tumaco' => 26000,
            'monteria' => 23000,
            'sincelejo' => 23000,
            'cartagena' => 24000,
            'barranquilla' => 24000,
            'santa marta' => 25000,
            'valledupar' => 25000,
            'riohacha' => 26000,
            'ocana' => 22000,
            'cucuta' => 23000,
            'apartado' => 26000,
            'quibdo' => 28000,
            'florencia' => 24000,
            'mitu' => 32000,
            'leticia' => 35000
        ];
    }

    private function calcularEnvio(string $ciudad): float {
        $ciudadNormalizada = $this->normalizarCiudadEnvio($ciudad);
        $tarifas = $this->tarifasEnvioPorCiudad();

        if (isset($tarifas[$ciudadNormalizada])) {
            return $tarifas[$ciudadNormalizada];
        }

        return 15000;
    }

    private function obtenerDireccionInicial(array $direcciones): ?array {
        $primera = null;
        foreach ($direcciones as $direccion) {
            if ($primera === null) {
                $primera = $direccion;
            }

            if ((int) ($direccion['es_predeterminada'] ?? 0) === 1) {
                return $direccion;
            }
        }

        return $primera;
    }

    private function calcularResumenCompra(float $subtotal, float $envio = 0): array {
        $subtotal = max(0, $subtotal);
        $envio = max(0, $envio);
        $iva = round($subtotal * 0.19, 2);
        $total = round($subtotal + $iva + $envio, 2);

        $resumen = [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'envio' => $envio,
            'total' => $total
        ];

        $_SESSION['checkout_resumen'] = $resumen;
        return $resumen;
    }

    private function crearVenta(int $idUsuario, array $resumenCompra, ?int $idCliente = null): int {
        $subtotal = (float) ($resumenCompra['subtotal'] ?? 0);
        $iva = (float) ($resumenCompra['iva'] ?? 0);
        $envio = (float) ($resumenCompra['envio'] ?? 0);
        $total = (float) ($resumenCompra['total'] ?? ($subtotal + $iva + $envio));
        $query = "INSERT INTO VENTA (
                    ID_USUARIO,
                    ID_CLIENTE,
                    ID_TIPO,
                    FECHA,
                    TOTAL,
                    SUBTOTAL,
                    IVA,
                    ENVIO,
                    METODO_PAGO,
                    ESTADO,
                    FECHA_PAGO
                  )
                  VALUES (
                    :id_usuario,
                    :id_cliente,
                    :id_tipo,
                    SYSDATE,
                    :total,
                    :subtotal,
                    :iva,
                    :envio,
                    NULL,
                    :estado,
                    NULL
                  )
                  RETURNING ID_VENTA INTO :id_venta";

        $stmt = oci_parse($this->conn, $query);
        $idVenta = null;
        $idTipoVenta = 2;
        $estado = 'PENDIENTE';
        $subtotal = number_format($subtotal, 2, '.', '');
        $iva = number_format($iva, 2, '.', '');
        $envio = number_format($envio, 2, '.', '');
        $total = number_format($total, 2, '.', '');

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_cliente', $idCliente, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_tipo', $idTipoVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':total', $total, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':subtotal', $subtotal, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':iva', $iva, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':envio', $envio, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':estado', $estado, -1, SQLT_CHR);
        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo crear la venta');
        }

        oci_free_statement($stmt);

        return (int) $idVenta;
    }

    private function crearDetallesVenta(int $idVenta, array $itemsPedido, ?array $productos = null): void {
        if (empty($itemsPedido)) {
            return;
        }

        $productos = $productos ?? $this->obtenerProductosResumen(array_keys($itemsPedido));
        $query = "INSERT INTO DETALLE_VENTA (
                    ID_VENTA,
                    ID_PRODUCTO,
                    CANTIDAD,
                    PRECIO_UNITARIO,
                    SUBTOTAL,
                    NOMBRE_PRODUCTO
                  )
                  VALUES (
                    :id_venta,
                    :id_producto,
                    :cantidad,
                    :precio_unitario,
                    :subtotal,
                    :nombre_producto
                  )";
        $stmt = oci_parse($this->conn, $query);
        $idProductoDetalle = 0;
        $cantidadDetalle = 0;
        $precioUnitarioValor = '0.00';
        $subtotalValor = '0.00';
        $nombreProducto = '';

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_producto', $idProductoDetalle, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':cantidad', $cantidadDetalle, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':precio_unitario', $precioUnitarioValor, 32, SQLT_CHR);
        oci_bind_by_name($stmt, ':subtotal', $subtotalValor, 32, SQLT_CHR);
        oci_bind_by_name($stmt, ':nombre_producto', $nombreProducto, 150, SQLT_CHR);

        foreach ($itemsPedido as $idProducto => $cantidad) {
            $producto = $productos[(int) $idProducto] ?? null;
            if (!$producto) {
                oci_free_statement($stmt);
                throw new Exception('Uno de los productos del pedido ya no existe');
            }

            $idProductoDetalle = (int) $idProducto;
            $cantidadDetalle = (int) $cantidad;
            $precioUnitario = (float) $producto['precio'];
            $precioUnitarioValor = number_format($precioUnitario, 2, '.', '');
            $subtotalValor = number_format(round($precioUnitario * $cantidadDetalle, 2), 2, '.', '');
            $nombreProducto = (string) $producto['nombre'];

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                throw new Exception($error['message'] ?? 'No se pudo guardar el detalle de la venta');
            }
        }

        oci_free_statement($stmt);
    }

    private function crearPedido(int $idVenta, int $estado = 1): int {
        $query = "INSERT INTO PEDIDO (ID_VENTA, ID_ESTADO)
                  VALUES (:id_venta, :estado)
                  RETURNING ID_PEDIDO INTO :id_pedido";

        $stmt = oci_parse($this->conn, $query);
        $idPedido = null;

        oci_bind_by_name($stmt, ':id_venta', $idVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':estado', $estado, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo crear el pedido');
        }

        oci_free_statement($stmt);

        return (int) $idPedido;
    }

    private function asignarDireccionPedido(int $idPedido, int $idDireccionPedido): void {
        $query = "UPDATE PEDIDO
                  SET ID_DIRECCION_PEDIDO = :id_direccion_pedido
                  WHERE ID_PEDIDO = :id_pedido";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_direccion_pedido', $idDireccionPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo asignar la direccion al pedido');
        }

        oci_free_statement($stmt);
    }

    private function obtenerDiasEntregaPorCiudad(string $ciudad): int {
        $dias = 4;
        $ciudad = trim($ciudad);
        if ($ciudad === '') {
            return $dias;
        }

        $query = "SELECT DIAS
                  FROM TIEMPO_ENTREGA
                  WHERE LOWER(CIUDAD) = LOWER(:ciudad)
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':ciudad', $ciudad);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            error_log($error['message'] ?? 'No se pudo consultar el tiempo de entrega');
            return $dias;
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row && isset($row['DIAS'])) {
            $dias = max(1, (int) $row['DIAS']);
        }

        return min($dias, 5);
    }

    private function guardarFechaEstimadaEntrega(int $idPedido, string $ciudad): string {
        $dias = $this->obtenerDiasEntregaPorCiudad($ciudad);
        $fechaEntrega = date('Y-m-d', strtotime("+$dias days"));

        $query = "UPDATE PEDIDO
                  SET FECHA_ESTIMADA_ENTREGA = TO_DATE(:fecha, 'YYYY-MM-DD')
                  WHERE ID_PEDIDO = :id_pedido";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':fecha', $fechaEntrega);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            error_log($error['message'] ?? 'No se pudo guardar la fecha estimada de entrega');
            throw new Exception('No se pudo guardar la fecha estimada de entrega');
        }

        oci_free_statement($stmt);

        return $fechaEntrega;
    }

    private function obtenerItemsPedidoActual(int $idPedido): array {
        $snapshot = $_SESSION['pedido_actual_items'][$idPedido] ?? null;
        if (is_array($snapshot)) {
            return $this->normalizarCarrito($snapshot);
        }

        $carrito = $this->obtenerCarritoSesion();
        return $this->normalizarCarrito($carrito);
    }

    private function descontarStockPedidoTx(array $itemsPedido): void {
        if (empty($itemsPedido)) {
            throw new Exception('No se encontraron productos para confirmar el pedido');
        }

        foreach ($itemsPedido as $idProducto => $cantidad) {
            $query = "SELECT NOMBRE, STOCK_P
                      FROM PRODUCTO
                      WHERE ID_PRODUCTO = :id_producto
                      FOR UPDATE";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                throw new Exception($error['message'] ?? 'No se pudo validar el stock');
            }

            $producto = oci_fetch_assoc($stmt);
            oci_free_statement($stmt);

            if (!$producto) {
                throw new Exception('Uno de los productos del pedido ya no existe');
            }

            $stockDisponible = (int) ($producto['STOCK_P'] ?? 0);
            if ($stockDisponible < $cantidad) {
                $nombre = (string) ($producto['NOMBRE'] ?? 'producto');
                throw new Exception('Stock insuficiente para ' . $nombre);
            }

            $query = "UPDATE PRODUCTO
                      SET STOCK_P = STOCK_P - :cantidad
                      WHERE ID_PRODUCTO = :id_producto";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':cantidad', $cantidad, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                throw new Exception($error['message'] ?? 'No se pudo descontar el stock');
            }

            oci_free_statement($stmt);
        }
    }

    private function reducirCarritoPedidoTx(int $idUsuario, array $itemsPedido): void {
        $idCarrito = $this->carritoModel->obtenerIdCarritoUsuario($idUsuario);
        if (!$idCarrito) {
            return;
        }

        foreach ($itemsPedido as $idProducto => $cantidadPedido) {
            $query = "SELECT ID_DETALLE, CANTIDAD
                      FROM DETALLE_CARRITO
                      WHERE ID_CARRITO = :id_carrito
                      AND ID_PRODUCTO = :id_producto
                      FOR UPDATE";

            $stmt = oci_parse($this->conn, $query);
            oci_bind_by_name($stmt, ':id_carrito', $idCarrito, -1, SQLT_INT);
            oci_bind_by_name($stmt, ':id_producto', $idProducto, -1, SQLT_INT);

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                throw new Exception($error['message'] ?? 'No se pudo actualizar el carrito');
            }

            $detalle = oci_fetch_assoc($stmt);
            oci_free_statement($stmt);

            if (!$detalle) {
                continue;
            }

            $cantidadActual = (int) ($detalle['CANTIDAD'] ?? 0);
            $idDetalle = (int) ($detalle['ID_DETALLE'] ?? 0);
            $cantidadRestante = $cantidadActual - $cantidadPedido;

            if ($cantidadRestante > 0) {
                $query = "UPDATE DETALLE_CARRITO
                          SET CANTIDAD = :cantidad
                          WHERE ID_DETALLE = :id_detalle";
                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ':cantidad', $cantidadRestante, -1, SQLT_INT);
                oci_bind_by_name($stmt, ':id_detalle', $idDetalle, -1, SQLT_INT);
            } else {
                $query = "DELETE FROM DETALLE_CARRITO
                          WHERE ID_DETALLE = :id_detalle";
                $stmt = oci_parse($this->conn, $query);
                oci_bind_by_name($stmt, ':id_detalle', $idDetalle, -1, SQLT_INT);
            }

            if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
                $error = oci_error($stmt);
                oci_free_statement($stmt);
                throw new Exception($error['message'] ?? 'No se pudo limpiar el carrito');
            }

            oci_free_statement($stmt);
        }
    }

    private function obtenerPedidoParaPago(int $idPedido, int $idUsuario): ?array {
        $query = "SELECT p.ID_PEDIDO,
                         p.ID_VENTA,
                         v.TOTAL
                  FROM PEDIDO p
                  INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                  WHERE p.ID_PEDIDO = :id_pedido
                  AND v.ID_USUARIO = :id_usuario
                  AND p.ID_ESTADO = 1";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el pedido para pago');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function obtenerIdEstadoCancelado(): int {
        $query = "SELECT ID_ESTADO
                  FROM ESTADO_PEDIDO
                  WHERE LOWER(NOMBRE) LIKE '%cancel%'
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el estado cancelado');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if ($row && (int) ($row['ID_ESTADO'] ?? 0) > 0) {
            return (int) $row['ID_ESTADO'];
        }

        $query = "SELECT NVL(MAX(ID_ESTADO), 0) + 1 AS ID_ESTADO
                  FROM ESTADO_PEDIDO";

        $stmt = oci_parse($this->conn, $query);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo calcular el estado cancelado');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        $idEstado = (int) ($row['ID_ESTADO'] ?? 0);
        if ($idEstado <= 0) {
            throw new Exception('No se pudo calcular el estado cancelado');
        }

        $query = "INSERT INTO ESTADO_PEDIDO (ID_ESTADO, NOMBRE)
                  VALUES (:id_estado, 'Cancelado')";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_estado', $idEstado, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo crear el estado cancelado');
        }

        oci_free_statement($stmt);
        return (int) $idEstado;
    }

    private function cancelarPedidoPendiente(int $idPedido, int $idUsuario): bool {
        $idCancelado = $this->obtenerIdEstadoCancelado();

        $query = "UPDATE PEDIDO p
                  SET p.ID_ESTADO = :id_cancelado
                  WHERE p.ID_PEDIDO = :id_pedido
                  AND p.ID_ESTADO = 1
                  AND EXISTS (
                      SELECT 1
                      FROM VENTA v
                      WHERE v.ID_VENTA = p.ID_VENTA
                      AND v.ID_USUARIO = :id_usuario
                  )";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_cancelado', $idCancelado, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo cancelar el pedido');
        }

        $updated = oci_num_rows($stmt) > 0;
        oci_free_statement($stmt);

        return $updated;
    }

    private function obtenerDetallePedidoUsuario(int $idPedido, int $idUsuario): ?array {
        $query = "SELECT p.ID_PEDIDO,
                         p.ID_ESTADO,
                         p.FECHA_ESTIMADA_ENTREGA,
                         v.ID_VENTA,
                         v.FECHA,
                         v.TOTAL,
                         e.NOMBRE AS ESTADO,
                         dp.NOMBRE_RECEPTOR,
                         dp.APELLIDO_RECEPTOR,
                         dp.DIRECCION_ENVIO,
                         dp.CIUDAD,
                         dp.BARRIO,
                         dp.TELEFONO_RECEPTOR,
                         dp.INFORMACION_ADICIONAL
                  FROM PEDIDO p
                  INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                  INNER JOIN ESTADO_PEDIDO e ON e.ID_ESTADO = p.ID_ESTADO
                  LEFT JOIN DIRECCION_PEDIDO dp ON dp.ID_DIRECCION_PEDIDO = p.ID_DIRECCION_PEDIDO
                  WHERE p.ID_PEDIDO = :id_pedido
                  AND v.ID_USUARIO = :id_usuario";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el detalle del pedido');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $row ? array_change_key_case($row, CASE_LOWER) : null;
    }

    private function registrarPago(int $idVenta, int $metodo, float $monto): void {
        $query = "BEGIN SP_PROCESAR_PAGO(:venta, :metodo, :monto); END;";

        $stmt = oci_parse($this->conn, $query);
        $monto = number_format($monto, 2, '.', '');

        oci_bind_by_name($stmt, ':venta', $idVenta, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':metodo', $metodo, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':monto', $monto, -1, SQLT_CHR);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo procesar el pago');
        }

        oci_free_statement($stmt);
    }

    public function resumen() {
        $this->ensureSession();

        $carrito = $this->normalizarCarrito($this->obtenerCarritoSesion());
        $items = [];
        $productos = $this->obtenerProductosResumen(array_keys($carrito));
        $total = $this->calcularTotalProductos($carrito, $productos);

        foreach ($carrito as $idProducto => $cantidad) {
            $producto = $productos[(int) $idProducto] ?? null;
            if (!$producto) {
                continue;
            }

            $precio = (float) $producto['precio'];
            $subtotal = $precio * $cantidad;

            $items[] = [
                'id_producto' => (int) $idProducto,
                'nombre' => $producto['nombre'],
                'precio' => $precio,
                'cantidad' => (int) $cantidad,
                'subtotal' => $subtotal
            ];
        }

        if (empty($items)) {
            $_SESSION['error'] = 'Tu carrito esta vacio';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        $resumenCompra = $this->calcularResumenCompra($total);
        require_once __DIR__ . '/../views/pedidos/resumen.php';
    }

    public function ConfirmarPedido() {
        $this->ensureSession();

        $pedidoConfirmado = $_SESSION['pedido_confirmado'] ?? null;
        if ($pedidoConfirmado) {
            unset($_SESSION['pedido_confirmado']);
            $direcciones = [];
            $total = (float) ($pedidoConfirmado['total'] ?? 0);
            require_once __DIR__ . '/../views/ConfirmarPedido.php';
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

        $this->direccionPedidoModel->obtenerDirecciones($idUsuario);
        $direcciones = $_SESSION['direcciones'] ?? [];
        $direccionInicial = $this->obtenerDireccionInicial($direcciones);
        $envioInicial = $direccionInicial ? $this->calcularEnvio((string) ($direccionInicial['ciudad'] ?? '')) : 0;
        $resumenCompra = $this->calcularResumenCompra($this->calcularTotalCarrito($carrito), $envioInicial);
        $total = $resumenCompra['total'];

        require_once __DIR__ . '/../views/ConfirmarPedido.php';
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
            'informacion_adicional' => $_POST['informacion_adicional'] ?? '',
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

        header("Location: index.php?action=ConfirmarPedido");
        exit();
    }

    public function editarDireccion() {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();
        $idDireccion = (int) ($_POST['direccion'] ?? ($_POST['id_direccion'] ?? 0));

        if ($idUsuario <= 0) {
            $this->responderDireccion(['success' => false, 'message' => 'Debes iniciar sesion'], 401);
        }

        $data = [
            'nombre_receptor' => $_POST['nombre_receptor'] ?? '',
            'apellido_receptor' => $_POST['apellido_receptor'] ?? '',
            'direccion_envio' => $_POST['direccion_envio'] ?? '',
            'ciudad' => $_POST['ciudad'] ?? '',
            'barrio' => $_POST['barrio'] ?? '',
            'telefono_receptor' => $_POST['telefono_receptor'] ?? '',
            'telefono_alterno' => $_POST['telefono_alterno'] ?? '',
            'informacion_adicional' => $_POST['informacion_adicional'] ?? '',
            'es_predeterminada' => $_POST['es_predeterminada'] ?? 0
        ];

        $resultado = $this->direccionPedidoModel->actualizarDireccion($idDireccion, $idUsuario, $data);
        $this->responderDireccion($resultado, $resultado['success'] ? 200 : 422);
    }

    public function eliminarDireccion() {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();
        $idDireccion = (int) ($_POST['direccion'] ?? ($_POST['id_direccion'] ?? 0));

        if ($idUsuario <= 0) {
            $this->responderDireccion(['success' => false, 'message' => 'Debes iniciar sesion'], 401);
        }

        $resultado = $this->direccionPedidoModel->eliminarDireccion($idDireccion, $idUsuario);
        $this->responderDireccion($resultado, $resultado['success'] ? 200 : 422);
    }

    public function editarDireccionPedido() {
        $this->ensureSession();
        $resultado = $this->direccionPedidoModel->actualizarDireccionPedidoPendiente((int) ($_POST['id_pedido'] ?? 0), $_POST);
        $this->responderDireccion($resultado, $resultado['success'] ? 200 : 422);
    }

    private function responderDireccion(array $resultado, int $status = 200): void {
        if ($this->isAjaxRequest()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resultado);
            exit();
        }

        if ($resultado['success']) {
            $_SESSION['success'] = 'Direccion actualizada correctamente';
        } else {
            $_SESSION['error'] = $resultado['message'] ?? 'No se pudo procesar la direccion';
        }

        header("Location: index.php?action=ConfirmarPedido");
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

        $idDireccion = (int) ($_POST['direccion'] ?? ($_POST['id_direccion'] ?? 0));
        if ($idDireccion <= 0) {
            $_SESSION['error'] = 'Selecciona una direccion de envio';
            header("Location: index.php?action=ConfirmarPedido");
            exit();
        }

        $direccion = $this->direccionPedidoModel->obtenerDireccionPorId($idDireccion);
        if (!$direccion || (int) $direccion['id_usuario'] !== $idUsuario) {
            $_SESSION['error'] = 'La direccion seleccionada no es valida';
            header("Location: index.php?action=ConfirmarPedido");
            exit();
        }

        $carrito = $this->normalizarCarrito($this->obtenerCarritoSesion());
        if (empty($carrito)) {
            $_SESSION['error'] = 'Tu carrito esta vacio';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        try {
            $productos = $this->obtenerProductosResumen(array_keys($carrito));
            $envio = $this->calcularEnvio((string) ($direccion['ciudad'] ?? ''));
            $resumenCompra = $this->calcularResumenCompra($this->calcularTotalProductos($carrito, $productos), $envio);
            $total = $resumenCompra['total'];
            $idPersona = $this->obtenerPersonaUsuario($idUsuario);
            $idCliente = $this->asegurarClientePorPersona($idPersona);
            $itemsPedido = $carrito;
            $idVenta = $this->crearVenta($idUsuario, $resumenCompra, $idCliente);
            $this->crearDetallesVenta($idVenta, $itemsPedido, $productos);
            $idPedido = $this->crearPedido($idVenta, 1);
            $idDireccionPedido = $this->direccionPedidoModel->copiarDireccionParaPedido($idPedido, $idDireccion, $idUsuario, $direccion, false);
            $this->asignarDireccionPedido($idPedido, $idDireccionPedido);
            $fechaEstimadaEntrega = $this->guardarFechaEstimadaEntrega($idPedido, (string) ($direccion['ciudad'] ?? ''));

            oci_commit($this->conn);
            $_SESSION['pedido_actual_items'][$idPedido] = $itemsPedido;
            $_SESSION['carrito_count'] = array_sum($carrito);
            $_SESSION['pedido_confirmado'] = [
                'id_pedido' => $idPedido,
                'id_venta' => $idVenta,
                'total' => $total,
                'subtotal' => $resumenCompra['subtotal'],
                'iva' => $resumenCompra['iva'],
                'envio' => $resumenCompra['envio'],
                'fecha_estimada_entrega' => $fechaEstimadaEntrega
            ];
            $_SESSION['pedido_actual'] = $idPedido;

            header("Location: index.php?action=pago");
            exit();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $_SESSION['error'] = 'No se pudo procesar el pedido. Verifica la informacion e intenta de nuevo.';
            header("Location: index.php?action=ConfirmarPedido");
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

    public function pago() {
        $this->ensureSession();

        $idPedido = (int) ($_SESSION['pedido_actual'] ?? ($_GET['id'] ?? 0));
        $idUsuario = $this->getUsuarioId();
        if ($idPedido <= 0) {
            $_SESSION['error'] = 'No hay un pedido pendiente de pago';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para pagar el pedido';
            header("Location: index.php?action=login");
            exit();
        }

        $pedidoPago = $this->obtenerPedidoParaPago($idPedido, $idUsuario);
        if (!$pedidoPago) {
            $_SESSION['error'] = 'No se encontro el pedido para pago';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        $_SESSION['pedido_actual'] = $idPedido;
        $_SESSION['venta_actual'] = (int) $pedidoPago['id_venta'];
        $_SESSION['monto_pago_actual'] = (float) $pedidoPago['total'];

        $pedido = array_merge($_SESSION['pedido_confirmado'] ?? [], [
            'id_pedido' => $idPedido,
            'id_venta' => (int) $pedidoPago['id_venta'],
            'total' => (float) $pedidoPago['total']
        ]);

        require_once __DIR__ . '/../views/pagos/pago.php';
    }

    public function procesarPago() {
        $this->ensureSession();

        $idPedido = (int) ($_SESSION['pedido_actual'] ?? 0);
        $idUsuario = $this->getUsuarioId();
        $metodo = (int) ($_POST['metodo_pago'] ?? 0);
        $metodosValidos = [1, 2, 3, 4];

        if ($idPedido <= 0) {
            $_SESSION['error'] = 'No hay un pedido pendiente de pago';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para procesar el pago';
            header("Location: index.php?action=login");
            exit();
        }

        if (!in_array($metodo, $metodosValidos, true)) {
            $_SESSION['error'] = 'Selecciona un metodo de pago valido';
            header("Location: index.php?action=pago");
            exit();
        }

        try {
            $pedidoPago = $this->obtenerPedidoParaPago($idPedido, $idUsuario);
            if (!$pedidoPago) {
                throw new Exception('No se encontro el pedido para procesar el pago');
            }

            $itemsPedido = $this->obtenerItemsPedidoActual($idPedido);
            $this->descontarStockPedidoTx($itemsPedido);
            $this->reducirCarritoPedidoTx($idUsuario, $itemsPedido);
            $this->registrarPago((int) $pedidoPago['id_venta'], $metodo, (float) $pedidoPago['total']);
            oci_commit($this->conn);
            unset(
                $_SESSION['pedido_actual'],
                $_SESSION['venta_actual'],
                $_SESSION['monto_pago_actual'],
                $_SESSION['pedido_confirmado'],
                $_SESSION['pedido_actual_items'][$idPedido],
                $_SESSION['carrito'],
                $_SESSION['carrito_mapa_cache'],
                $_SESSION['tienda_cache']
            );
            $_SESSION['carrito_count'] = $this->carritoModel->obtenerTotalItemsCarrito($idUsuario);
            $_SESSION['success'] = 'Pago simulado correctamente. Inventario actualizado y carrito sincronizado.';
            header("Location: index.php?action=misPedidos");
            exit();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $_SESSION['error'] = $e->getMessage() ?: 'No se pudo procesar el pago';
            header("Location: index.php?action=pago");
            exit();
        }
    }

    public function cancelarPedido() {
        $this->ensureSession();

        $idPedido = (int) ($_POST['id_pedido'] ?? 0);
        $idUsuario = $this->getUsuarioId();

        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para cancelar un pedido';
            header("Location: index.php?action=login");
            exit();
        }

        if ($idPedido <= 0) {
            $_SESSION['error'] = 'Pedido invalido';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        try {
            $cancelado = $this->cancelarPedidoPendiente($idPedido, $idUsuario);
            if (!$cancelado) {
                oci_rollback($this->conn);
                $_SESSION['error'] = 'Solo puedes cancelar pedidos que sigan en estado pendiente';
                header("Location: index.php?action=misPedidos&id=" . $idPedido);
                exit();
            }

            oci_commit($this->conn);
            if ((int) ($_SESSION['pedido_actual'] ?? 0) === $idPedido) {
                unset($_SESSION['pedido_actual'], $_SESSION['venta_actual'], $_SESSION['monto_pago_actual'], $_SESSION['pedido_confirmado']);
            }
            unset($_SESSION['pedido_actual_items'][$idPedido]);

            $_SESSION['success'] = 'Pedido cancelado correctamente';
            header("Location: index.php?action=misPedidos&id=" . $idPedido);
            exit();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $_SESSION['error'] = 'No se pudo cancelar el pedido';
            header("Location: index.php?action=misPedidos&id=" . $idPedido);
            exit();
        }
    }

    public function misPedidos() {
        $this->ensureSession();

        $id_usuario = (int) ($_SESSION['usuario']['id_usuario'] ?? ($_SESSION['id_usuario'] ?? 0));
        if ($id_usuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para ver tus pedidos';
            header("Location: index.php?action=login");
            exit();
        }

        $idPedidoDetalle = (int) ($_GET['id'] ?? 0);
        if ($idPedidoDetalle > 0) {
            try {
                $pedidoDetalle = $this->obtenerDetallePedidoUsuario($idPedidoDetalle, $id_usuario);
            } catch (Exception $e) {
                error_log($e->getMessage());
                $_SESSION['error'] = 'No se pudo consultar el detalle del pedido';
                header("Location: index.php?action=misPedidos");
                exit();
            }

            if (!$pedidoDetalle) {
                $_SESSION['error'] = 'No encontramos ese pedido en tu cuenta';
                header("Location: index.php?action=misPedidos");
                exit();
            }

            $pedidos = [];
            require_once __DIR__ . '/../views/pedidos/mis_pedidos.php';
            return;
        }

        $query = "SELECT ID_PEDIDO,
                         FECHA,
                         TOTAL,
                         ESTADO
                  FROM V_PEDIDOS_USUARIO
                  WHERE ID_USUARIO = :id_usuario
                  ORDER BY FECHA DESC";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ":id_usuario", $id_usuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            error_log($error['message'] ?? 'No se pudieron consultar los pedidos');
            $_SESSION['error'] = 'No se pudieron consultar tus pedidos';
            header("Location: index.php?action=inicio");
            exit();
        }

        $pedidos = [];

        while ($row = oci_fetch_assoc($stmt)) {
            $pedidos[] = array_change_key_case($row, CASE_LOWER);
        }

        oci_free_statement($stmt);

        require_once __DIR__ . '/../views/pedidos/mis_pedidos.php';
    }
}
