<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/DireccionPedidoModel.php';
require_once __DIR__ . '/../models/MetodoPagoUsuarioModel.php';
require_once __DIR__ . '/../models/WompiApiModel.php';
require_once __DIR__ . '/../models/PedidoLifecycleModel.php';
require_once __DIR__ . '/CheckoutController.php';

class PedidoController {

    private $conn;
    private $carritoModel;
    private $direccionPedidoModel;
    private $metodoPagoUsuarioModel;
    private $pedidoLifecycleModel;
    private $ventaColumnasCache = null;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->carritoModel = new CarritoModel($this->conn);
        $this->direccionPedidoModel = new DireccionPedidoModel($this->conn);
        $this->metodoPagoUsuarioModel = new MetodoPagoUsuarioModel($this->conn);
        $this->pedidoLifecycleModel = new PedidoLifecycleModel($this->conn);
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

    private function expirarPedidosPendientes(): void {
        try {
            $this->pedidoLifecycleModel->expirarPendientes();
            oci_commit($this->conn);
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('SP_EXPIRAR_PEDIDOS: ' . $e->getMessage());
        }
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

    private function prepararPedidoConfirmado(array $pedido, string $metodoPago = 'Registrado'): array {
        $items = isset($pedido['items']) && is_array($pedido['items']) ? $pedido['items'] : [];
        $subtotalItems = array_sum(array_map(fn($item) => (float) ($item['subtotal'] ?? 0), $items));
        $total = (float) ($pedido['total'] ?? 0);
        $subtotalItems = (float) ($pedido['subtotal'] ?? $subtotalItems);
        $iva = (float) ($pedido['iva'] ?? ($subtotalItems > 0 ? round($subtotalItems * 0.19, 2) : 0));
        $envio = (float) ($pedido['envio'] ?? max(0, $total - $subtotalItems - $iva));

        if ($subtotalItems <= 0) {
            $subtotalItems = max(0, round($total / 1.19, 2));
            $iva = max(0, $total - $subtotalItems);
            $envio = 0;
        }

        return [
            'id_pedido' => (int) ($pedido['id_pedido'] ?? 0),
            'id_venta' => (int) ($pedido['id_venta'] ?? 0),
            'fecha' => $pedido['fecha'] ?? null,
            'total' => $total,
            'subtotal' => $subtotalItems,
            'iva' => $iva,
            'envio' => $envio,
            'fecha_estimada_entrega' => $pedido['fecha_estimada_entrega'] ?? null,
            'metodo_pago' => $metodoPago,
            'items' => $items,
            'receptor' => [
                'nombre' => trim((string) (($pedido['nombre_receptor'] ?? '') . ' ' . ($pedido['apellido_receptor'] ?? ''))),
                'direccion' => (string) ($pedido['direccion_envio'] ?? ''),
                'ciudad' => (string) ($pedido['ciudad'] ?? ''),
                'telefono' => (string) ($pedido['telefono_receptor'] ?? '')
            ]
        ];
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
            if (!preg_match('/^(0[1-9]|1[0-2])\/\d{4}$/', $vencimiento)) {
                $errores[] = 'Ingresa el vencimiento en formato MM/YYYY';
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
        return;
    }

    private function obtenerPedidosUsuario(int $idUsuario): array {
        $venta = $this->expresionesResumenVenta();
        $estadoSql = $this->estadoPedidoSql();

        $query = "SELECT p.ID_PEDIDO,
                         p.ID_ESTADO,
                         $estadoSql AS ESTADO,
                         {$venta['fecha']} AS FECHA,
                         {$venta['total']} AS TOTAL,
                         TO_CHAR(p.CREATED_AT, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT,
                         GREATEST(0, FLOOR((CAST(p.CREATED_AT AS DATE) + (30 / 1440) - SYSDATE) * 86400)) AS SEGUNDOS_RESTANTES,
                         CASE WHEN EXISTS (
                             SELECT 1
                             FROM PAGO pg
                             WHERE pg.ID_VENTA = p.ID_VENTA
                               AND UPPER(TRIM(pg.ESTADO)) IN ('APPROVED', 'PAGADO', 'COMPLETADO')
                         ) THEN 1 ELSE 0 END AS PAGO_APROBADO,
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
        $query = "SELECT fp.ID_PEDIDO,
                         fp.ID_VENTA,
                         p.ID_ESTADO,
                         fp.ESTADO_PEDIDO AS ESTADO,
                         TO_CHAR(fp.FECHA, 'YYYY-MM-DD HH24:MI:SS') AS FECHA,
                         NVL(fp.SUBTOTAL, 0) AS SUBTOTAL,
                         NVL(fp.IVA, 0) AS IVA,
                         NVL(fp.ENVIO, 0) AS ENVIO,
                         NVL(fp.TOTAL, 0) AS TOTAL,
                         TO_CHAR(p.CREATED_AT, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT,
                         GREATEST(0, FLOOR((CAST(p.CREATED_AT AS DATE) + (30 / 1440) - SYSDATE) * 86400)) AS SEGUNDOS_RESTANTES,
                         CASE WHEN EXISTS (
                             SELECT 1
                             FROM PAGO pg
                             WHERE pg.ID_VENTA = p.ID_VENTA
                               AND UPPER(TRIM(pg.ESTADO)) IN ('APPROVED', 'PAGADO', 'COMPLETADO')
                         ) THEN 1 ELSE 0 END AS PAGO_APROBADO,
                         CAST(NULL AS VARCHAR2(10)) AS FECHA_ESTIMADA_ENTREGA,
                         fp.NOMBRE_RECEPTOR,
                         fp.APELLIDO_RECEPTOR,
                         fp.DIRECCION_ENVIO,
                         fp.CIUDAD,
                         fp.BARRIO,
                         fp.TELEFONO_RECEPTOR,
                         CAST(NULL AS VARCHAR2(50)) AS TELEFONO_ALTERNO,
                         CAST(NULL AS VARCHAR2(500)) AS INFORMACION_ADICIONAL
                  FROM V_FACTURA_PEDIDO fp
                  INNER JOIN PEDIDO p ON p.ID_PEDIDO = fp.ID_PEDIDO
                  WHERE fp.ID_USUARIO = :id_usuario
                    AND fp.ID_PEDIDO = :id_pedido
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
            $metodo = $this->metodoPagoUsuarioModel->obtenerPorIdUsuario($idMetodo, $idUsuario);
            if ($metodo && (int) ($metodo['id_fuente_wompi'] ?? 0) > 0) {
                try {
                    (new WompiApiModel())->anularFuentePago((int) $metodo['id_fuente_wompi']);
                } catch (Throwable $wompiError) {
                    error_log('Wompi anular fuente: ' . $wompiError->getMessage());
                }
            }

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
        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{4})$/', $vencimiento, $matches)) {
            return $matches[1] . '/' . $matches[2];
        }
        if (preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $vencimiento, $matches)) {
            return $matches[1] . '/20' . $matches[2];
        }
        if (preg_match('/^\d{4}-\d{2}$/', $vencimiento)) {
            return substr($vencimiento, 5, 2) . '/' . substr($vencimiento, 0, 4);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencimiento)) {
            return substr($vencimiento, 5, 2) . '/' . substr($vencimiento, 0, 4);
        }

        throw new InvalidArgumentException('Ingresa el vencimiento en formato MM/YYYY');
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

    private function correoUsuario(int $idUsuario): string {
        $query = "SELECT LOWER(TRIM(p.CORREO)) AS CORREO
                  FROM USUARIO u
                  INNER JOIN PERSONA p ON p.ID_PERSONA = u.ID_PERSONA
                  WHERE u.ID_USUARIO = :id_usuario
                  FETCH FIRST 1 ROWS ONLY";

        $stmt = oci_parse($this->conn, $query);
        if (!$stmt) {
            throw new Exception('No se pudo consultar el correo del usuario');
        }

        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);
        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            throw new Exception($error['message'] ?? 'No se pudo consultar el correo del usuario');
        }

        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        $correo = trim((string) ($row['CORREO'] ?? ''));
        if ($correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Tu usuario no tiene un correo valido para Wompi');
        }

        return $correo;
    }

    public function wompiTarjetasConfig(): void {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $this->responderPagoGuardado(false, 'Debes iniciar sesion', 401, ['redirect' => 'index.php?action=login']);
        }

        try {
            $api = new WompiApiModel();
            $aceptaciones = $api->obtenerAceptaciones();
            $this->responderPagoGuardado(true, 'Configuracion Wompi disponible', 200, [
                'public_key' => $api->publicKey(),
                'acceptance_token' => $aceptaciones['acceptance_token'],
                'acceptance_permalink' => $aceptaciones['acceptance_permalink'],
                'personal_auth_permalink' => $aceptaciones['personal_auth_permalink']
            ]);
        } catch (Throwable $e) {
            error_log('wompiTarjetasConfig: ' . $e->getMessage());
            $this->responderPagoGuardado(false, 'No se pudo cargar la configuracion de Wompi', 500);
        }
    }

    public function guardarMetodoPagoUsuario(): void {
        $this->ensureSession();
        $idUsuario = $this->getUsuarioId();

        if ($idUsuario <= 0) {
            $this->responderPagoGuardado(false, 'Debes iniciar sesion para guardar una tarjeta', 401, ['redirect' => 'index.php?action=login']);
        }

        try {
            $tokenWompi = trim((string) ($_POST['token_wompi'] ?? ''));
            $marca = strtoupper(trim((string) ($_POST['marca'] ?? $_POST['franquicia'] ?? '')));
            $ultimos4 = preg_replace('/\D+/', '', (string) ($_POST['ultimos_4'] ?? ''));
            $alias = trim((string) ($_POST['alias_tarjeta'] ?? $_POST['titular_tarjeta'] ?? ''));
            $fechaExpiracion = $this->fechaExpiracionMetodoPago((string) ($_POST['fecha_expiracion'] ?? $_POST['vencimiento_tarjeta'] ?? ''));

            error_log('[guardar] POST recibido: token=' . $tokenWompi . ' marca=' . $marca . ' ultimos4=' . $ultimos4 . ' alias=' . $alias . ' fecha=' . $fechaExpiracion);

            if ($tokenWompi === '' || strlen($ultimos4) !== 4 || $alias === '') {
                throw new InvalidArgumentException('No se recibio una tarjeta tokenizada valida de Wompi');
            }
            if (!in_array($marca, ['VISA', 'MASTERCARD'], true)) {
                throw new InvalidArgumentException('Solo se permiten tarjetas VISA o MASTERCARD');
            }

            $fechaExpiracionDate = DateTime::createFromFormat('!m/Y', $fechaExpiracion);
            if (!$fechaExpiracionDate || $fechaExpiracionDate->modify('last day of this month') < new DateTime('today')) {
                throw new InvalidArgumentException('La tarjeta esta vencida');
            }

            $api = new WompiApiModel();
            error_log('[guardar] Llamando crearFuenteTarjeta token=' . $tokenWompi);
            $fuente = $api->crearFuenteTarjeta($tokenWompi, $this->correoUsuario($idUsuario));
            error_log('[guardar] FUENTE WOMPI RESPONSE: ' . json_encode($fuente));

            $idFuenteWompi = (int) ($fuente['id'] ?? 0);
            $estadoWompi = strtoupper((string) ($fuente['status'] ?? 'AVAILABLE'));
            $publicData = is_array($fuente['public_data'] ?? null) ? $fuente['public_data'] : [];
            $marca = strtoupper(trim((string) ($publicData['brand'] ?? $publicData['card_brand'] ?? $marca)));
            $ultimos4 = preg_replace('/\D+/', '', (string) ($publicData['last_four'] ?? $publicData['card_last_four'] ?? $ultimos4));

            $idMetodo = (int) ($_POST['id_metodo'] ?? 3);
            if (!in_array($idMetodo, [2, 3], true)) {
                $idMetodo = 3;
            }

            $datosGuardar = [
                'id_usuario' => $idUsuario,
                'id_metodo' => $idMetodo,
                'titular' => $alias,
                'ultimos_4' => $ultimos4,
                'franquicia' => $marca,
                'token_wompi' => $tokenWompi,
                'id_fuente_wompi' => $idFuenteWompi,
                'estado_wompi' => $estadoWompi,
                'fecha_expiracion' => $fechaExpiracion,
                'es_predeterminado' => (int) ($_POST['es_predeterminado_pago'] ?? $_POST['es_predeterminado'] ?? 0)
            ];
            error_log('[guardar] DATOS GUARDAR: ' . json_encode($datosGuardar));

            $idMetodoPagoUsuario = $this->metodoPagoUsuarioModel->guardar($datosGuardar);
            error_log('[guardar] id_metodo_pago_usuario generado: ' . $idMetodoPagoUsuario);

            oci_commit($this->conn);
            $this->responderPagoGuardado(true, 'Tarjeta guardada de forma segura con Wompi', 200, [
                'id_metodo_pago_usuario' => $idMetodoPagoUsuario
            ]);
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log('[guardar] ERROR ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            $this->responderPagoGuardado(false, $e->getMessage(), 400);
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
            $fechaExpiracionDate = DateTime::createFromFormat('!m/Y', $fechaExpiracion);
            if (!$fechaExpiracionDate || $fechaExpiracionDate->modify('last day of this month') < new DateTime('today')) {
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
        $this->expirarPedidosPendientes();

        $idPedido = (int) ($_GET['id'] ?? 0);
        $pedido = $_SESSION['pedido_confirmado'] ?? null;

        if ($idPedido > 0 && (!is_array($pedido) || (int) ($pedido['id_pedido'] ?? 0) !== $idPedido)) {
            $idUsuario = $this->getUsuarioId();
            if ($idUsuario <= 0) {
                $_SESSION['error'] = 'Debes iniciar sesion para ver la confirmacion del pedido';
                header("Location: index.php?action=login");
                exit();
            }

            try {
                $pedidoDb = $this->obtenerPedidoUsuario($idUsuario, $idPedido);
                $pedido = $pedidoDb ? $this->prepararPedidoConfirmado($pedidoDb) : null;
                if ($pedidoDb && !in_array((int) ($pedidoDb['id_estado'] ?? 0), [2, 3, 4], true)) {
                    $_SESSION['error'] = 'La confirmacion y la factura estaran disponibles cuando Wompi apruebe el pago.';
                    header("Location: index.php?action=misPedidos&id=" . $idPedido);
                    exit();
                }
            } catch (Throwable $e) {
                error_log($e->getMessage());
                $pedido = null;
            }
        }

        if (!is_array($pedido) || (int) ($pedido['id_pedido'] ?? 0) <= 0) {
            header("Location: index.php?action=tienda");
            exit();
        }

        require_once __DIR__ . '/../views/pedidos/confirmacion.php';
    }

    public function misPedidos() {
        $this->ensureSession();
        $this->expirarPedidosPendientes();
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
        $this->expirarPedidosPendientes();

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

        $idEstadoPedido = (int) ($pedido['id_estado'] ?? 0);
        $pagoAprobadoPedido = (int) ($pedido['pago_aprobado'] ?? 0) === 1;
        if (!in_array($idEstadoPedido, [2, 3, 4], true) && !($idEstadoPedido === 5 && $pagoAprobadoPedido)) {
            $_SESSION['error'] = 'La factura estara disponible cuando el pago sea aprobado.';
            header("Location: index.php?action=misPedidos&id=" . $idPedido);
            exit();
        }

        $pedidoConfirmado = $this->prepararPedidoConfirmado($pedido);

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

        $facturaPedido = $pedidoConfirmado;
        $facturaRootTag = 'main';
        $facturaHideActions = false;
        require_once __DIR__ . '/../views/helpers/entrega.php';
        require_once __DIR__ . '/../views/layouts/navbar.php';
        require_once __DIR__ . '/../views/pedidos/factura_moderna.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    public function cancelarPedido() {
        $this->ensureSession();
        $this->expirarPedidosPendientes();

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

        try {
            $pedidoActual = $this->obtenerPedidoUsuario($idUsuario, $idPedido);
            if (!$pedidoActual) {
                $_SESSION['error'] = 'Pedido no encontrado';
                header("Location: index.php?action=misPedidos");
                exit();
            }

            $estadoActual = (int) ($pedidoActual['id_estado'] ?? 0);
            if ($estadoActual === 3) {
                $_SESSION['error'] = 'El pedido ya esta en camino';
                header("Location: index.php?action=misPedidos&id=" . $idPedido);
                exit();
            }
            if ($estadoActual === 4) {
                $_SESSION['error'] = 'No es posible cancelar este pedido porque ya fue entregado';
                header("Location: index.php?action=misPedidos&id=" . $idPedido);
                exit();
            }
            if ($estadoActual === 5) {
                $_SESSION['success'] = 'Pedido cancelado';
                header("Location: index.php?action=misPedidos&id=" . $idPedido);
                exit();
            }
            if (!in_array($estadoActual, [1, 2], true)) {
                $_SESSION['error'] = 'No es posible cancelar este pedido';
                header("Location: index.php?action=misPedidos&id=" . $idPedido);
                exit();
            }
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $_SESSION['error'] = 'No es posible cancelar este pedido';
            header("Location: index.php?action=misPedidos&id=" . $idPedido);
            exit();
        }

        $query = "BEGIN SP_CANCELAR_PEDIDO(:id_pedido, :id_usuario); END;";

        $stmt = oci_parse($this->conn, $query);
        oci_bind_by_name($stmt, ':id_pedido', $idPedido, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':id_usuario', $idUsuario, -1, SQLT_INT);

        if (!@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
            $error = oci_error($stmt);
            oci_free_statement($stmt);
            oci_rollback($this->conn);
            $message = (string) ($error['message'] ?? 'No es posible cancelar este pedido');
            error_log($message);
            if (str_contains($message, 'camino')) {
                $_SESSION['error'] = 'El pedido ya esta en camino';
            } elseif (str_contains($message, 'entregado')) {
                $_SESSION['error'] = 'No es posible cancelar este pedido porque ya fue entregado';
            } elseif (str_contains($message, 'cancelado')) {
                $_SESSION['success'] = 'Pedido cancelado';
            } else {
                $_SESSION['error'] = 'No es posible cancelar este pedido';
            }
            header("Location: index.php?action=misPedidos&id=" . $idPedido);
            exit();
        }

        oci_free_statement($stmt);

        oci_commit($this->conn);
        $_SESSION['success'] = 'Pedido cancelado correctamente';
        header("Location: index.php?action=misPedidos");
        exit();
    }
}
