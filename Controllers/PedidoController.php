<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/DireccionPedidoModel.php';

class PedidoController {

    private $conn;
    private $carritoModel;
    private $direccionPedidoModel;
    private $ventaColumnasCache = null;
    private $pedidoColumnasCache = null;

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

    private function obtenerColumnasTabla(string $tabla): array {
        if ($tabla === 'VENTA' && isset($_SESSION['venta_columnas_cache']) && is_array($_SESSION['venta_columnas_cache'])) {
            return $_SESSION['venta_columnas_cache'];
        }

        if ($tabla === 'VENTA' && $this->ventaColumnasCache !== null) {
            return $this->ventaColumnasCache;
        }

        if ($tabla === 'PEDIDO' && $this->pedidoColumnasCache !== null) {
            return $this->pedidoColumnasCache;
        }

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

        if ($tabla === 'VENTA') {
            $this->ventaColumnasCache = $columnas;
            $_SESSION['venta_columnas_cache'] = $columnas;
        }

        if ($tabla === 'PEDIDO') {
            $this->pedidoColumnasCache = $columnas;
        }

        return $columnas;
    }

    private function crearVenta(int $idUsuario, float $total, ?int $idCliente = null): int {
        $columnas = $this->obtenerColumnasTabla('VENTA');
        $insertColumns = [];
        $valueExpressions = [];
        $binds = [];

        foreach ($columnas as $lowerName => $meta) {
            if ($lowerName === 'id_venta' || strtoupper($meta['identity']) === 'YES') {
                continue;
            }

            if ($lowerName === 'id_cliente') {
                $insertColumns[] = $meta['name'];
                if ($idCliente !== null && $idCliente > 0) {
                    $valueExpressions[] = ':id_cliente';
                    $binds[':id_cliente'] = ['value' => $idCliente, 'type' => SQLT_INT];
                } else {
                    $valueExpressions[] = 'NULL';
                }
                continue;
            }

            if ($lowerName === 'id_tipo') {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = ':id_tipo_venta';
                $binds[':id_tipo_venta'] = ['value' => 2, 'type' => SQLT_INT];
                continue;
            }

            if ($lowerName === 'id_usuario') {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = ':' . $lowerName;
                $binds[':' . $lowerName] = ['value' => $idUsuario, 'type' => SQLT_INT];
                continue;
            }

            if (in_array($lowerName, ['total', 'total_venta', 'total_pagar', 'valor_total', 'monto_total'], true)) {
                $insertColumns[] = $meta['name'];
                $valueExpressions[] = ':' . $lowerName;
                $binds[':' . $lowerName] = ['value' => number_format($total, 2, '.', ''), 'type' => SQLT_CHR];
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

    private function actualizarPagoPedido(int $idPedido, int $metodo): void {
        $columnas = $this->obtenerColumnasTabla('PEDIDO');
        $sets = [];
        $binds = [
            ':id_pedido' => ['value' => $idPedido, 'type' => SQLT_INT]
        ];

        if (isset($columnas['id_metodo'])) {
            $sets[] = $columnas['id_metodo']['name'] . ' = :metodo';
            $binds[':metodo'] = ['value' => $metodo, 'type' => SQLT_INT];
        } elseif (isset($columnas['id_metodo_pago'])) {
            $sets[] = $columnas['id_metodo_pago']['name'] . ' = :metodo';
            $binds[':metodo'] = ['value' => $metodo, 'type' => SQLT_INT];
        }

        if (isset($columnas['estado'])) {
            $sets[] = $columnas['estado']['name'] . " = :estado_pago";
            $binds[':estado_pago'] = ['value' => 'Pagado', 'type' => SQLT_CHR];
        } elseif (isset($columnas['id_estado'])) {
            $sets[] = $columnas['id_estado']['name'] . " = :id_estado_pago";
            $binds[':id_estado_pago'] = ['value' => 2, 'type' => SQLT_INT];
        }

        if (empty($sets)) {
            throw new Exception('La tabla PEDIDO no tiene columnas configuradas para registrar el pago');
        }

        $query = "UPDATE PEDIDO
                  SET " . implode(', ', $sets) . "
                  WHERE ID_PEDIDO = :id_pedido";

        $stmt = oci_parse($this->conn, $query);
        foreach ($binds as $placeholder => &$bind) {
            oci_bind_by_name($stmt, $placeholder, $bind['value'], -1, $bind['type']);
        }
        unset($bind);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo procesar el pago');
        }

        oci_free_statement($stmt);
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

        $carrito = $this->obtenerCarritoSesion();
        if (empty($carrito)) {
            $_SESSION['error'] = 'Tu carrito esta vacio';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        try {
            $envio = $this->calcularEnvio((string) ($direccion['ciudad'] ?? ''));
            $resumenCompra = $this->calcularResumenCompra($this->calcularTotalCarrito($carrito), $envio);
            $total = $resumenCompra['total'];
            $idPersona = $this->obtenerPersonaUsuario($idUsuario);
            $idCliente = $this->asegurarClientePorPersona($idPersona);
            $idVenta = $this->crearVenta($idUsuario, $total, $idCliente);
            $idPedido = $this->crearPedido($idVenta, 1);
            $idDireccionPedido = $this->direccionPedidoModel->copiarDireccionParaPedido($idPedido, $idDireccion, $idUsuario, $direccion, false);
            $this->asignarDireccionPedido($idPedido, $idDireccionPedido);
            $fechaEstimadaEntrega = $this->guardarFechaEstimadaEntrega($idPedido, (string) ($direccion['ciudad'] ?? ''));

            $this->carritoModel->vaciarCarritoTx($idUsuario);
            oci_commit($this->conn);
            unset($_SESSION['carrito'], $_SESSION['carrito_mapa_cache']);
            $_SESSION['carrito_count'] = 0;
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
        if ($idPedido <= 0) {
            $_SESSION['error'] = 'No hay un pedido pendiente de pago';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        $_SESSION['pedido_actual'] = $idPedido;
        $pedido = $_SESSION['pedido_confirmado'] ?? [
            'id_pedido' => $idPedido,
            'total' => 0
        ];

        require_once __DIR__ . '/../views/pagos/pago.php';
    }

    public function procesarPago() {
        $this->ensureSession();

        $idPedido = (int) ($_SESSION['pedido_actual'] ?? 0);
        $metodo = (int) ($_POST['metodo_pago'] ?? 0);
        $metodosValidos = [1, 2, 3, 4];

        if ($idPedido <= 0) {
            $_SESSION['error'] = 'No hay un pedido pendiente de pago';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        if (!in_array($metodo, $metodosValidos, true)) {
            $_SESSION['error'] = 'Selecciona un metodo de pago valido';
            header("Location: index.php?action=pago");
            exit();
        }

        try {
            $this->actualizarPagoPedido($idPedido, $metodo);
            oci_commit($this->conn);
            unset($_SESSION['pedido_actual'], $_SESSION['pedido_confirmado']);
            $_SESSION['success'] = 'Pago simulado correctamente';
            header("Location: index.php?action=misPedidos");
            exit();
        } catch (Exception $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $_SESSION['error'] = 'No se pudo procesar el pago';
            header("Location: index.php?action=pago");
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

        $query = "SELECT p.ID_PEDIDO,
                         p.ID_VENTA,
                         p.ID_ESTADO,
                         p.FECHA_ESTIMADA_ENTREGA,
                         v.FECHA,
                         v.TOTAL
                  FROM PEDIDO p
                  INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                  WHERE v.ID_USUARIO = :id_usuario
                  ORDER BY v.FECHA DESC";

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
