<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/DireccionPedidoModel.php';
require_once __DIR__ . '/../models/MetodoPagoUsuarioModel.php';
require_once __DIR__ . '/CheckoutController.php';

class PedidoController {

    private $conn;
    private $carritoModel;
    private $direccionPedidoModel;
    private $metodoPagoUsuarioModel;
    private $ventaColumnasCache = null;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->carritoModel = new CarritoModel($this->conn);
        $this->direccionPedidoModel = new DireccionPedidoModel($this->conn);
        $this->metodoPagoUsuarioModel = new MetodoPagoUsuarioModel($this->conn);
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

    private function obtenerProductosResumen(array $ids): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));
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

        oci_execute($stmt);

        $productos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $productos[(int) $row['ID_PRODUCTO']] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);

        return $productos;
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

    private function responderPagoGuardado(bool $success, string $message, int $status = 200, array $extra = []): void {
        if ($this->isAjaxRequest()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array_merge([
                'success' => $success,
                'message' => $message
            ], $extra));
            exit();
        }

        $_SESSION[$success ? 'success' : 'error'] = $message;
        header('Location: index.php?action=pago');
        exit();
    }

    private function calcularTotalCarrito(array $carrito): float {
        $productos = $this->obtenerProductosResumen(array_keys($carrito));

        $total = 0;
        foreach ($carrito as $idProducto => $cantidad) {
            $idProducto = (int) $idProducto;
            if (!isset($productos[$idProducto])) {
                continue;
            }

            $total += (float) $productos[$idProducto]['precio'] * (int) $cantidad;
        }

        return $total;
    }

    private function obtenerItemsFactura(array $carrito): array {
        $items = [];
        $productos = $this->obtenerProductosResumen(array_keys($carrito));

        foreach ($carrito as $idProducto => $cantidad) {
            $idProducto = (int) $idProducto;
            $cantidad = (int) $cantidad;
            if ($idProducto <= 0 || $cantidad <= 0) {
                continue;
            }

            $producto = $productos[$idProducto] ?? null;
            if (!$producto) {
                continue;
            }

            $precio = (float) $producto['precio'];
            $items[] = [
                'id_producto' => $idProducto,
                'nombre' => (string) $producto['nombre'],
                'cantidad' => $cantidad,
                'precio' => $precio,
                'subtotal' => $precio * $cantidad
            ];
        }

        return $items;
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

    private function limpiarParteNombreArchivo(string $value, string $fallback): string {
        $value = trim($value);
        if ($value === '') {
            return $fallback;
        }

        $sinAcentos = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($sinAcentos !== false) {
            $value = $sinAcentos;
        }

        $value = preg_replace('/[\\\\\/:*?"<>|#]+/', '', $value);
        $value = preg_replace('/\s+/', '-', trim((string) $value));
        $value = preg_replace('/-+/', '-', (string) $value);

        return $value !== '' ? $value : $fallback;
    }

    private function nombreArchivoFactura(array $pedidoConfirmado, array $pedido): string {
        $idPedido = (int) ($pedidoConfirmado['id_pedido'] ?? 0);
        $receptor = trim((string) ($pedidoConfirmado['receptor']['nombre'] ?? 'Cliente'));
        $partesNombre = preg_split('/\s+/', $receptor);
        $primerNombre = $this->limpiarParteNombreArchivo((string) ($partesNombre[0] ?? 'Cliente'), 'Cliente');
        $fechaRaw = (string) ($pedido['fecha'] ?? '');
        $timestamp = $fechaRaw !== '' ? strtotime($fechaRaw) : false;
        $fecha = $timestamp ? date('d-m-Y', $timestamp) : date('d-m-Y');

        return sprintf('Factura #%d-%s-%s.html', $idPedido, $primerNombre, $fecha);
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

    private function obtenerResumenCheckoutRapido(int $idUsuario, array $direccion): array {
        $resumenSeleccionados = $this->carritoModel->obtenerResumenSeleccionadosRapido($idUsuario);
        if ((int) ($resumenSeleccionados['total_items'] ?? 0) <= 0) {
            return [
                'resumen' => [],
                'items_validos' => false
            ];
        }

        $envio = $this->calcularEnvio((string) ($direccion['ciudad'] ?? ''));

        return [
            'resumen' => $this->calcularResumenCompra((float) ($resumenSeleccionados['total_pagar'] ?? 0), $envio),
            'items_validos' => true
        ];
    }

    private function nombreMetodoPago(int $metodo): string {
        return match ($metodo) {
            1 => 'Efectivo',
            2 => 'Tarjeta debito',
            3 => 'Tarjeta credito',
            4 => 'Transferencia bancaria',
            default => 'No seleccionado'
        };
    }

    private function validarDatosPago(array $data): array {
        $metodo = (int) ($data['metodo_pago'] ?? 0);
        $errores = [];

        if (!in_array($metodo, [1, 2, 3, 4], true)) {
            $errores[] = 'Selecciona un metodo de pago valido';
        }

        if ($metodo === 1) {
            if (trim((string) ($data['nombre_pagador'] ?? '')) === '') {
                $errores[] = 'Escribe el nombre de quien paga en efectivo';
            }
            if (trim((string) ($data['documento_pagador'] ?? '')) === '') {
                $errores[] = 'Escribe el documento de quien paga en efectivo';
            }
        }

        if (in_array($metodo, [2, 3], true)) {
            $numero = preg_replace('/\D+/', '', (string) ($data['numero_tarjeta'] ?? ''));
            $cvv = preg_replace('/\D+/', '', (string) ($data['cvv_tarjeta'] ?? ''));
            $vencimiento = trim((string) ($data['vencimiento_tarjeta'] ?? ''));

            if (strlen($numero) < 13 || strlen($numero) > 19) {
                $errores[] = 'Ingresa un numero de tarjeta valido';
            }
            if (trim((string) ($data['titular_tarjeta'] ?? '')) === '') {
                $errores[] = 'Ingresa el nombre del titular';
            }
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $vencimiento)) {
                $errores[] = 'Ingresa el vencimiento en formato MM/AA';
            }
            if (strlen($cvv) < 3 || strlen($cvv) > 4) {
                $errores[] = 'Ingresa un CVV valido';
            }
        }

        if ($metodo === 4) {
            if (trim((string) ($data['banco_origen'] ?? '')) === '') {
                $errores[] = 'Escribe el banco de origen';
            }
            if (trim((string) ($data['referencia_transferencia'] ?? '')) === '') {
                $errores[] = 'Escribe la referencia de transferencia';
            }
        }

        return $errores;
    }

    private function obtenerColumnasTabla(string $tabla): array {
        if ($tabla === 'VENTA' && $this->ventaColumnasCache !== null) {
            return $this->ventaColumnasCache;
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
            unset($_SESSION['venta_columnas_cache']);
        }

        return $columnas;
    }

    private function columnaVentaDisponible(array $columnas, array $candidatas): ?string {
        foreach ($candidatas as $columna) {
            $key = strtolower($columna);
            if (isset($columnas[$key])) {
                return $columnas[$key]['name'];
            }
        }

        return null;
    }

    private function expresionesResumenVenta(): array {
        $columnasVenta = $this->obtenerColumnasTabla('VENTA');
        $columnaFecha = $this->columnaVentaDisponible($columnasVenta, ['fecha', 'fecha_venta', 'fecha_creacion', 'created_at']);
        $columnaTotal = $this->columnaVentaDisponible($columnasVenta, ['total', 'total_venta', 'total_pagar', 'valor_total', 'monto_total']);

        return [
            'fecha' => $columnaFecha ? "TO_CHAR(v.$columnaFecha, 'YYYY-MM-DD HH24:MI:SS')" : "''",
            'total' => $columnaTotal ? "NVL(v.$columnaTotal, 0)" : '0'
        ];
    }

    private function estadoPedidoSql(): string {
        return "CASE p.ID_ESTADO
                    WHEN 1 THEN 'Pendiente'
                    WHEN 2 THEN 'Procesado'
                    WHEN 3 THEN 'Enviado'
                    WHEN 4 THEN 'Entregado'
                    WHEN 5 THEN 'Cancelado'
                    ELSE 'Pendiente'
                END";
    }

    private function expresionesDetalleVenta(string $alias = 'dv', string $productoAlias = 'p'): array {
        $columnas = $this->obtenerColumnasTabla('DETALLE_VENTA');
        $precio = isset($columnas['precio_unitario']) ? "NVL($alias.PRECIO_UNITARIO, $productoAlias.PRECIO)" : "$productoAlias.PRECIO";
        $subtotal = isset($columnas['subtotal']) ? "NVL($alias.SUBTOTAL, $precio * $alias.CANTIDAD)" : "($precio * $alias.CANTIDAD)";
        $nombre = isset($columnas['nombre_producto']) ? "NVL($alias.NOMBRE_PRODUCTO, $productoAlias.NOMBRE)" : "$productoAlias.NOMBRE";

        return [
            'nombre' => $nombre,
            'precio' => $precio,
            'subtotal' => $subtotal
        ];
    }

    private function actualizarEstadosPedidosUsuario(int $idUsuario): void {
        $columnasVenta = $this->obtenerColumnasTabla('VENTA');
        $columnaFecha = $this->columnaVentaDisponible($columnasVenta, ['fecha', 'fecha_venta', 'fecha_creacion', 'created_at']);
        if (!$columnaFecha) {
            return;
        }

        $query = "UPDATE PEDIDO p
                  SET p.ID_ESTADO = (
                      SELECT CASE
                          WHEN ((SYSDATE - v.$columnaFecha) * 1440) >= 15 THEN 4
                          WHEN ((SYSDATE - v.$columnaFecha) * 1440) >= 10 THEN GREATEST(p.ID_ESTADO, 3)
                          WHEN ((SYSDATE - v.$columnaFecha) * 1440) >= 5 THEN GREATEST(p.ID_ESTADO, 2)
                          ELSE p.ID_ESTADO
                      END
                      FROM VENTA v
                      WHERE v.ID_VENTA = p.ID_VENTA
                        AND v.ID_USUARIO = :id_usuario
                  )
                  WHERE p.ID_ESTADO IN (1, 2, 3)
                    AND EXISTS (
                        SELECT 1
                        FROM VENTA v
                        WHERE v.ID_VENTA = p.ID_VENTA
                          AND v.ID_USUARIO = :id_usuario
                    )";

        $stmt = @oci_parse($this->conn, $query);
        if (!$stmt) {
            return;
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            oci_free_statement($stmt);
            oci_rollback($this->conn);
            return;
        }

        oci_free_statement($stmt);
        oci_commit($this->conn);
    }

    private function obtenerPedidosUsuario(int $idUsuario): array {
        $venta = $this->expresionesResumenVenta();
        $estadoSql = $this->estadoPedidoSql();

        $query = "SELECT p.ID_PEDIDO,
                         p.ID_ESTADO,
                         $estadoSql AS ESTADO,
                         {$venta['fecha']} AS FECHA,
                         {$venta['total']} AS TOTAL,
                         TO_CHAR(p.FECHA_ESTIMADA_ENTREGA, 'YYYY-MM-DD') AS FECHA_ESTIMADA_ENTREGA
                  FROM PEDIDO p
                  INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                  WHERE v.ID_USUARIO = :id_usuario
                  ORDER BY p.ID_PEDIDO DESC";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudieron consultar los pedidos');
        }

        $pedidos = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $pedidos[] = array_change_key_case($row, CASE_LOWER);
        }

        oci_free_statement($stmt);
        return $pedidos;
    }

    private function adjuntarItemsResumenPedidos(int $idUsuario, array $pedidos): array {
        if (empty($pedidos)) {
            return $pedidos;
        }

        $ids = array_values(array_filter(array_map(fn($pedido) => (int) ($pedido['id_pedido'] ?? 0), $pedidos), fn($id) => $id > 0));
        if (empty($ids)) {
            return $pedidos;
        }

        $placeholders = [];
        $binds = [];
        foreach ($ids as $index => $idPedido) {
            $placeholder = ':pedido' . $index;
            $placeholders[] = $placeholder;
            $binds[$placeholder] = $idPedido;
        }

        $detalle = $this->expresionesDetalleVenta();

        $query = "SELECT pe.ID_PEDIDO,
                         dv.ID_PRODUCTO,
                         {$detalle['nombre']} AS NOMBRE,
                         dv.CANTIDAD,
                         (SELECT MIN(pi.URL) KEEP (DENSE_RANK FIRST ORDER BY NVL(pi.ORDEN, 999999), pi.ID_IMAGEN)
                          FROM PRODUCTO_IMAGEN pi
                          WHERE pi.ID_PRODUCTO = dv.ID_PRODUCTO) AS IMAGEN
                  FROM PEDIDO pe
                  INNER JOIN VENTA v ON v.ID_VENTA = pe.ID_VENTA
                  INNER JOIN DETALLE_VENTA dv ON dv.ID_VENTA = pe.ID_VENTA
                  LEFT JOIN PRODUCTO p ON p.ID_PRODUCTO = dv.ID_PRODUCTO
                  WHERE v.ID_USUARIO = :id_usuario
                    AND pe.ID_PEDIDO IN (" . implode(', ', $placeholders) . ")
                  ORDER BY pe.ID_PEDIDO DESC, dv.ID_PRODUCTO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        foreach ($binds as $placeholder => &$value) {
            oci_bind_by_name($stmt, $placeholder, $value, -1, SQLT_INT);
        }
        unset($value);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            error_log($error['message'] ?? 'No se pudieron consultar las miniaturas de pedidos');
            return $pedidos;
        }

        $itemsPorPedido = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $idPedido = (int) $row['ID_PEDIDO'];
            if (!isset($itemsPorPedido[$idPedido])) {
                $itemsPorPedido[$idPedido] = [];
            }

            $itemsPorPedido[$idPedido][] = [
                'id_producto' => (int) ($row['ID_PRODUCTO'] ?? 0),
                'nombre' => (string) ($row['NOMBRE'] ?? 'Producto'),
                'cantidad' => (int) ($row['CANTIDAD'] ?? 0),
                'imagen' => $row['IMAGEN'] ?? null
            ];
        }
        oci_free_statement($stmt);

        foreach ($pedidos as &$pedido) {
            $idPedido = (int) ($pedido['id_pedido'] ?? 0);
            $items = $itemsPorPedido[$idPedido] ?? [];
            $pedido['items_preview'] = $items;
            $pedido['cantidad_productos'] = array_sum(array_map(fn($item) => (int) ($item['cantidad'] ?? 0), $items));
        }
        unset($pedido);

        return $pedidos;
    }

    private function obtenerPedidoUsuario(int $idUsuario, int $idPedido): ?array {
        $venta = $this->expresionesResumenVenta();
        $estadoSql = $this->estadoPedidoSql();

        $query = "SELECT p.ID_PEDIDO,
                         p.ID_ESTADO,
                         $estadoSql AS ESTADO,
                         {$venta['fecha']} AS FECHA,
                         {$venta['total']} AS TOTAL,
                         TO_CHAR(p.FECHA_ESTIMADA_ENTREGA, 'YYYY-MM-DD') AS FECHA_ESTIMADA_ENTREGA,
                         dp.NOMBRE_RECEPTOR,
                         dp.APELLIDO_RECEPTOR,
                         dp.DIRECCION_ENVIO,
                         dp.CIUDAD,
                         dp.BARRIO,
                         dp.TELEFONO_RECEPTOR,
                         dp.TELEFONO_ALTERNO,
                         dp.INFORMACION_ADICIONAL
                  FROM PEDIDO p
                  INNER JOIN VENTA v ON v.ID_VENTA = p.ID_VENTA
                  LEFT JOIN DIRECCION_PEDIDO dp ON dp.ID_DIRECCION_PEDIDO = p.ID_DIRECCION_PEDIDO
                  WHERE v.ID_USUARIO = :id_usuario
                    AND p.ID_PEDIDO = :id_pedido
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el pedido');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        if (!$row) {
            return null;
        }

        $pedido = array_change_key_case($row, CASE_LOWER);
        $pedido['items'] = $this->obtenerItemsPedidoUsuario($idUsuario, $idPedido);

        return $pedido;
    }

    private function obtenerItemsPedidoUsuario(int $idUsuario, int $idPedido): array {
        $detalle = $this->expresionesDetalleVenta();

        $query = "SELECT dv.ID_PRODUCTO,
                         {$detalle['nombre']} AS NOMBRE,
                         dv.CANTIDAD,
                         {$detalle['precio']} AS PRECIO,
                         {$detalle['subtotal']} AS SUBTOTAL,
                         (SELECT MIN(pi.URL) KEEP (DENSE_RANK FIRST ORDER BY NVL(pi.ORDEN, 999999), pi.ID_IMAGEN)
                          FROM PRODUCTO_IMAGEN pi
                          WHERE pi.ID_PRODUCTO = dv.ID_PRODUCTO) AS IMAGEN
                  FROM DETALLE_VENTA dv
                  INNER JOIN PEDIDO pe ON pe.ID_VENTA = dv.ID_VENTA
                  INNER JOIN VENTA v ON v.ID_VENTA = pe.ID_VENTA
                  LEFT JOIN PRODUCTO p ON p.ID_PRODUCTO = dv.ID_PRODUCTO
                  WHERE pe.ID_PEDIDO = :id_pedido
                    AND v.ID_USUARIO = :id_usuario
                  ORDER BY dv.ID_PRODUCTO";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            error_log($error['message'] ?? 'No se pudieron consultar los productos del pedido');
            return [];
        }

        $items = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $items[] = array_change_key_case($row, CASE_LOWER);
        }

        oci_free_statement($stmt);
        return $items;
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

    public function resumen() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        $items = $idUsuario > 0 ? $this->carritoModel->obtenerItemsVisualizacion($idUsuario, true) : [];
        $total = array_sum(array_map(fn($item) => (float) ($item['total_linea'] ?? 0), $items));

        if (empty($items)) {
            $_SESSION['error'] = 'Selecciona al menos un producto para confirmar el pedido';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        $resumenCompra = $this->calcularResumenCompra($total);
        require_once __DIR__ . '/../views/pedidos/resumen.php';
    }

    public function ConfirmarPedido() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para continuar la compra';
            header("Location: index.php?action=login");
            exit();
        }

        $itemsSeleccionados = $this->carritoModel->obtenerItemsVisualizacion($idUsuario, true);
        if (empty($itemsSeleccionados)) {
            $_SESSION['error'] = 'Selecciona al menos un producto para confirmar el pedido';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        $this->direccionPedidoModel->obtenerDirecciones($idUsuario);
        $direcciones = $_SESSION['direcciones'] ?? [];
        $direccionInicial = $this->obtenerDireccionInicial($direcciones);
        $envioInicial = $direccionInicial ? $this->calcularEnvio((string) ($direccionInicial['ciudad'] ?? '')) : 0;
        $subtotalSeleccionado = array_sum(array_map(fn($item) => (float) ($item['total_linea'] ?? 0), $itemsSeleccionados));
        $itemsCheckout = $itemsSeleccionados;
        $resumenCompra = $this->calcularResumenCompra($subtotalSeleccionado, $envioInicial);
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
        $this->responderDireccion($resultado, $resultado['success'] ? 200 : 422, 'Direccion actualizada correctamente');
    }

    public function eliminarDireccion() {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();
        $idDireccion = (int) ($_POST['direccion'] ?? ($_POST['id_direccion'] ?? 0));

        if ($idUsuario <= 0) {
            $this->responderDireccion(['success' => false, 'message' => 'Debes iniciar sesion'], 401);
        }

        $resultado = $this->direccionPedidoModel->eliminarDireccion($idDireccion, $idUsuario);
        $this->responderDireccion($resultado, $resultado['success'] ? 200 : 422, 'Direccion eliminada correctamente');
    }

    public function editarDireccionPedido() {
        $this->ensureSession();
        $idPedido = (int) ($_POST['id_pedido'] ?? 0);
        $resultado = $this->direccionPedidoModel->actualizarDireccionPedidoPendiente($idPedido, $_POST);

        if ($this->isAjaxRequest()) {
            $this->responderDireccion($resultado, $resultado['success'] ? 200 : 422);
        }

        if ($resultado['success']) {
            $_SESSION['success'] = 'Direccion del pedido actualizada correctamente';
        } else {
            $_SESSION['error'] = $resultado['message'] ?? 'No se pudo actualizar la direccion del pedido';
        }

        header("Location: index.php?action=misPedidos&id=" . $idPedido);
        exit();
    }

    private function responderDireccion(array $resultado, int $status = 200, string $successMessage = 'Direccion actualizada correctamente'): void {
        if ($this->isAjaxRequest()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($resultado);
            exit();
        }

        if ($resultado['success']) {
            $_SESSION['success'] = $successMessage;
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

        $resumenSeleccionados = $this->carritoModel->obtenerResumenSeleccionadosRapido($idUsuario);
        if ((int) ($resumenSeleccionados['total_items'] ?? 0) <= 0) {
            $_SESSION['error'] = 'Selecciona al menos un producto para confirmar el pedido';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        $envio = $this->calcularEnvio((string) ($direccion['ciudad'] ?? ''));
        $resumenCompra = $this->calcularResumenCompra((float) ($resumenSeleccionados['total_pagar'] ?? 0), $envio);
        $_SESSION['checkout_direccion_id'] = $idDireccion;
        $_SESSION['checkout_direccion_snapshot'] = $direccion;
        $_SESSION['checkout_fecha_estimada_entrega'] = date('Y-m-d', strtotime('+' . $this->obtenerDiasEntregaPorCiudad((string) ($direccion['ciudad'] ?? '')) . ' days'));

        header("Location: index.php?action=pago");
        exit();
    }

    public function pago() {
        $this->ensureSession();
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para pagar';
            header("Location: index.php?action=login");
            exit();
        }

        $idDireccion = (int) ($_SESSION['checkout_direccion_id'] ?? 0);
        if ($idDireccion <= 0) {
            $_SESSION['error'] = 'Selecciona una direccion antes de elegir el metodo de pago';
            header("Location: index.php?action=ConfirmarPedido");
            exit();
        }

        $direccionSnapshot = $_SESSION['checkout_direccion_snapshot'] ?? null;
        if (is_array($direccionSnapshot) && (int) ($direccionSnapshot['id_direccion'] ?? $direccionSnapshot['id_direccion_pedido'] ?? $idDireccion) === $idDireccion) {
            $direccion = $direccionSnapshot;
        } else {
            $direccion = $this->direccionPedidoModel->obtenerDireccionPorId($idDireccion);
            if (!$direccion || (int) $direccion['id_usuario'] !== $idUsuario) {
                unset($_SESSION['checkout_direccion_id'], $_SESSION['checkout_direccion_snapshot'], $_SESSION['checkout_resumen']);
                $_SESSION['error'] = 'La direccion seleccionada no esta disponible';
                header("Location: index.php?action=ConfirmarPedido");
                exit();
            }
            $_SESSION['checkout_direccion_snapshot'] = $direccion;
        }

        $checkoutRapido = $this->obtenerResumenCheckoutRapido($idUsuario, $direccion);
        if (!$checkoutRapido['items_validos']) {
            $_SESSION['error'] = 'Selecciona al menos un producto para pagar';
            header("Location: index.php?action=verCarrito");
            exit();
        }

        $resumenCompra = $checkoutRapido['resumen'];
        $total = $resumenCompra['total'];
        $fechaEstimadaEntrega = $_SESSION['checkout_fecha_estimada_entrega'] ?? date('Y-m-d', strtotime('+' . $this->obtenerDiasEntregaPorCiudad((string) ($direccion['ciudad'] ?? '')) . ' days'));
        $metodosPagoUsuario = $this->metodoPagoUsuarioModel->obtenerPorUsuario($idUsuario, 0);
        if (empty($metodosPagoUsuario)) {
            $this->metodoPagoUsuarioModel->invalidarCacheUsuario($idUsuario);
            $metodosPagoUsuario = $this->metodoPagoUsuarioModel->obtenerPorUsuario($idUsuario, 0);
        }
        $metodoPagoPredeterminado = null;
        foreach ($metodosPagoUsuario as $metodoGuardado) {
            if ((int) ($metodoGuardado['activo'] ?? 0) === 1 && (int) ($metodoGuardado['es_predeterminado'] ?? 0) === 1) {
                $metodoPagoPredeterminado = $metodoGuardado;
                break;
            }
        }

        require_once __DIR__ . '/../views/pagos/pago.php';
    }

    public function eliminarMetodoPagoUsuario(): void {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();
        $idMetodo = (int) ($_POST['id_metodo_pago_usuario'] ?? 0);

        if ($idUsuario <= 0) {
            $this->responderPagoGuardado(false, 'Debes iniciar sesion', 401, ['redirect' => 'index.php?action=login']);
        }

        try {
            $ok = $this->metodoPagoUsuarioModel->eliminar($idMetodo, $idUsuario);
            oci_commit($this->conn);
            $this->responderPagoGuardado($ok, $ok ? 'Metodo de pago eliminado correctamente' : 'No se pudo eliminar el metodo de pago', $ok ? 200 : 400);
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $this->responderPagoGuardado(false, 'No se pudo eliminar el metodo de pago', 500);
        }
    }

    public function cambiarEstadoMetodoPagoUsuario(): void {
        $this->ensureSession();
        $this->responderPagoGuardado(false, 'Las tarjetas solo se desactivan automaticamente cuando vencen.', 403);
    }

    private function fechaExpiracionMetodoPago(string $vencimiento): string {
        $vencimiento = trim($vencimiento);
        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $vencimiento, $matches)) {
            return sprintf('%04d-%02d-01', 2000 + (int) $matches[2], (int) $matches[1]);
        }
        if (preg_match('/^\d{4}-\d{2}$/', $vencimiento)) {
            return $vencimiento . '-01';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimiento)) {
            return substr($vencimiento, 0, 7) . '-01';
        }

        throw new InvalidArgumentException('Ingresa el vencimiento en formato MM/AA');
    }

    private function detectarFranquiciaTarjeta(string $numero): string {
        $numero = preg_replace('/\D+/', '', $numero);
        if (preg_match('/^4\d{12}(\d{3})?(\d{3})?$/', $numero)) {
            return 'VISA';
        }
        if (preg_match('/^(5[1-5]\d{14}|2(2[2-9]\d|[3-6]\d{2}|7[01]\d|720)\d{12})$/', $numero)) {
            return 'MASTERCARD';
        }

        return 'DESCONOCIDA';
    }

    private function generarTokenMetodoPago(): string {
        try {
            return 'tok_' . bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            return 'tok_' . hash('sha256', uniqid('', true) . microtime(true));
        }
    }

    public function guardarMetodoPagoUsuario(): void {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();

        if ($idUsuario <= 0) {
            $this->responderPagoGuardado(false, 'Debes iniciar sesion para guardar una tarjeta', 401, ['redirect' => 'index.php?action=login']);
        }

        try {
            $idMetodo = (int) ($_POST['id_metodo'] ?? $_POST['metodo_pago'] ?? 0);
            $numero = preg_replace('/\D+/', '', (string) ($_POST['numero_tarjeta'] ?? ''));
            $titular = trim((string) ($_POST['titular_tarjeta'] ?? $_POST['titular'] ?? ''));
            $vencimiento = trim((string) ($_POST['vencimiento_tarjeta'] ?? $_POST['fecha_expiracion'] ?? ''));
            $cvv = preg_replace('/\D+/', '', (string) ($_POST['cvv_tarjeta'] ?? ''));
            $franquicia = $this->detectarFranquiciaTarjeta($numero);

            if (!in_array($idMetodo, [2, 3], true)) {
                throw new InvalidArgumentException('Selecciona si la tarjeta es debito o credito');
            }
            if (strlen($numero) < 13 || strlen($numero) > 19 || $franquicia === 'DESCONOCIDA') {
                throw new InvalidArgumentException('Ingresa una tarjeta Visa o Mastercard valida');
            }
            if ($titular === '') {
                throw new InvalidArgumentException('Ingresa el nombre del titular');
            }
            if (strlen($cvv) < 3 || strlen($cvv) > 4) {
                throw new InvalidArgumentException('Ingresa un CVV valido');
            }

            $fechaExpiracion = $this->fechaExpiracionMetodoPago($vencimiento);
            if (strtotime($fechaExpiracion . ' +1 month -1 day') < strtotime(date('Y-m-d'))) {
                throw new InvalidArgumentException('La tarjeta esta vencida');
            }

            $idGuardado = $this->metodoPagoUsuarioModel->guardar([
                'id_usuario' => $idUsuario,
                'id_metodo' => $idMetodo,
                'titular' => $titular,
                'ultimos_4' => substr($numero, -4),
                'franquicia' => $franquicia,
                'token_pago' => $this->generarTokenMetodoPago(),
                'fecha_expiracion' => $fechaExpiracion,
                'es_predeterminado' => (int) ($_POST['es_predeterminado_pago'] ?? 0)
            ]);

            oci_commit($this->conn);
            $_SESSION['payment_old'] = [
                'metodo_pago' => $idMetodo,
                'id_metodo_pago_usuario' => $idGuardado
            ];
            $this->responderPagoGuardado(true, 'Tarjeta guardada correctamente. Seleccionala y confirma con CVV.');
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $_SESSION['payment_old'] = [
                'metodo_pago' => (int) ($_POST['id_metodo'] ?? 2),
                'titular_tarjeta' => trim((string) ($_POST['titular_tarjeta'] ?? '')),
                'vencimiento_tarjeta' => trim((string) ($_POST['vencimiento_tarjeta'] ?? '')),
                'mostrar_formulario_tarjeta' => 1
            ];
            $this->responderPagoGuardado(false, $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo guardar la tarjeta', 400);
        }
    }

    public function actualizarMetodoPagoUsuario(): void {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();
        $idMetodo = (int) ($_POST['id_metodo_pago_usuario'] ?? 0);

        if ($idUsuario <= 0) {
            $this->responderPagoGuardado(false, 'Debes iniciar sesion', 401, ['redirect' => 'index.php?action=login']);
        }

        try {
            $fechaExpiracion = $this->fechaExpiracionMetodoPago((string) ($_POST['fecha_expiracion'] ?? ''));
            if (strtotime($fechaExpiracion . ' +1 month -1 day') < strtotime(date('Y-m-d'))) {
                throw new InvalidArgumentException('La tarjeta esta vencida');
            }

            $ok = $this->metodoPagoUsuarioModel->actualizar($idMetodo, $idUsuario, [
                'titular' => $_POST['titular'] ?? '',
                'fecha_expiracion' => $fechaExpiracion,
                'es_predeterminado' => (int) ($_POST['es_predeterminado'] ?? 0)
            ]);
            oci_commit($this->conn);
            $this->responderPagoGuardado($ok, $ok ? 'Metodo de pago actualizado correctamente' : 'No se pudo actualizar el metodo de pago', $ok ? 200 : 400);
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $this->responderPagoGuardado(false, $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo actualizar el metodo de pago', 400);
        }
    }

    public function predeterminarMetodoPagoUsuario(): void {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();
        $idMetodo = (int) ($_POST['id_metodo_pago_usuario'] ?? 0);

        if ($idUsuario <= 0) {
            $this->responderPagoGuardado(false, 'Debes iniciar sesion', 401, ['redirect' => 'index.php?action=login']);
        }

        try {
            $ok = $this->metodoPagoUsuarioModel->establecerPredeterminado($idMetodo, $idUsuario);
            oci_commit($this->conn);
            $this->responderPagoGuardado($ok, $ok ? 'Metodo de pago predeterminado actualizado' : 'No se pudo actualizar el metodo de pago', $ok ? 200 : 400);
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());
            $this->responderPagoGuardado(false, 'No se pudo actualizar el metodo de pago', 500);
        }
    }

    public function procesarPago() {
        (new CheckoutController())->confirmarPedido();
        exit();
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

    public function misPedidos() {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();

        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para ver tus pedidos';
            header("Location: index.php?action=login");
            exit();
        }

        $pedidos = [];
        $pedidoDetalle = null;

        try {
            $idPedido = (int) ($_GET['id'] ?? 0);
            if ($idPedido > 0) {
                $pedidoDetalle = $this->obtenerPedidoUsuario($idUsuario, $idPedido);
                if (!$pedidoDetalle) {
                    $_SESSION['error'] = 'Pedido no encontrado';
                    header("Location: index.php?action=misPedidos");
                    exit();
                }
            } else {
                $pedidos = $this->obtenerPedidosUsuario($idUsuario);
                $pedidos = $this->adjuntarItemsResumenPedidos($idUsuario, $pedidos);
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'No se pudieron cargar tus pedidos en este momento';
        }

        require_once __DIR__ . '/../views/pedidos/mis_pedidos.php';
    }

    public function facturaPedido() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para descargar la factura';
            header("Location: index.php?action=login");
            exit();
        }

        $idPedido = (int) ($_GET['id'] ?? 0);
        if ($idPedido <= 0) {
            $_SESSION['error'] = 'Pedido invalido';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        $pedido = $this->obtenerPedidoUsuario($idUsuario, $idPedido);
        if (!$pedido) {
            $_SESSION['error'] = 'Pedido no encontrado';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        $items = isset($pedido['items']) && is_array($pedido['items']) ? $pedido['items'] : [];
        $subtotalItems = array_sum(array_map(fn($item) => (float) ($item['subtotal'] ?? 0), $items));
        $total = (float) ($pedido['total'] ?? 0);
        $iva = $subtotalItems > 0 ? round($subtotalItems * 0.19, 2) : 0;
        $envio = max(0, $total - $subtotalItems - $iva);
        if ($subtotalItems <= 0) {
            $subtotalItems = max(0, round($total / 1.19, 2));
            $iva = max(0, $total - $subtotalItems);
            $envio = 0;
        }

        $pedidoConfirmado = [
            'id_pedido' => (int) $pedido['id_pedido'],
            'id_venta' => 0,
            'fecha' => $pedido['fecha'] ?? null,
            'total' => $total,
            'subtotal' => $subtotalItems,
            'iva' => $iva,
            'envio' => $envio,
            'fecha_estimada_entrega' => $pedido['fecha_estimada_entrega'] ?? null,
            'metodo_pago' => 'Registrado',
            'items' => $items,
            'receptor' => [
                'nombre' => trim((string) (($pedido['nombre_receptor'] ?? '') . ' ' . ($pedido['apellido_receptor'] ?? ''))),
                'direccion' => (string) ($pedido['direccion_envio'] ?? ''),
                'ciudad' => (string) ($pedido['ciudad'] ?? ''),
                'telefono' => (string) ($pedido['telefono_receptor'] ?? '')
            ]
        ];

        if (!empty($_GET['download'])) {
            $facturaPedido = $pedidoConfirmado;
            $facturaDownloadMode = true;
            $filename = $this->nombreArchivoFactura($pedidoConfirmado, $pedido);

            header('Content-Type: text/html; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"" . addslashes($filename) . "\"; filename*=UTF-8''" . rawurlencode($filename));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');

            require_once __DIR__ . '/../views/helpers/entrega.php';
            require_once __DIR__ . '/../views/pedidos/factura_moderna.php';
            exit();
        }

        $direcciones = [];
        $total = (float) $pedidoConfirmado['total'];

        require_once __DIR__ . '/../views/ConfirmarPedido.php';
    }

    public function cancelarPedido() {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para cancelar un pedido';
            header("Location: index.php?action=login");
            exit();
        }

        $idPedido = (int) ($_POST['id_pedido'] ?? ($_GET['id'] ?? 0));
        if ($idPedido <= 0) {
            $_SESSION['error'] = 'Pedido invalido';
            header("Location: index.php?action=misPedidos");
            exit();
        }

        $query = "UPDATE PEDIDO p
                  SET p.ID_ESTADO = 5
                  WHERE p.ID_PEDIDO = :id_pedido
                    AND p.ID_ESTADO = 1
                    AND EXISTS (
                        SELECT 1
                        FROM VENTA v
                        WHERE v.ID_VENTA = p.ID_VENTA
                          AND v.ID_USUARIO = :id_usuario
                    )";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            oci_rollback($this->conn);
            error_log($error['message'] ?? 'No se pudo cancelar el pedido');
            $_SESSION['error'] = 'No se pudo cancelar el pedido';
            header("Location: index.php?action=misPedidos&id=" . $idPedido);
            exit();
        }

        $filas = oci_num_rows($stmt);
        oci_free_statement($stmt);

        if ($filas < 1) {
            oci_rollback($this->conn);
            $_SESSION['error'] = 'Solo puedes cancelar pedidos pendientes';
            header("Location: index.php?action=misPedidos&id=" . $idPedido);
            exit();
        }

        oci_commit($this->conn);
        $_SESSION['success'] = 'Pedido cancelado correctamente';
        header("Location: index.php?action=misPedidos");
        exit();
    }
}
