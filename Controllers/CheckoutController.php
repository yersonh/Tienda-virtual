<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/VentaModel.php';
require_once __DIR__ . '/../models/PagoModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';
require_once __DIR__ . '/../models/PedidoModel.php';
require_once __DIR__ . '/../models/DireccionPedidoModel.php';
require_once __DIR__ . '/../models/MetodoPagoUsuarioModel.php';

class CheckoutController {

    private $conn;
    private $ventaModel;
    private $pagoModel;
    private $carritoModel;
    private $pedidoModel;
    private $direccionPedidoModel;
    private $metodoPagoUsuarioModel;

    public function __construct() {
        $this->conn = Database::getConnection();
        $this->ventaModel = new VentaModel($this->conn);
        $this->pagoModel = new PagoModel($this->conn);
        $this->carritoModel = new CarritoModel($this->conn);
        $this->pedidoModel = new PedidoModel($this->conn);
        $this->direccionPedidoModel = new DireccionPedidoModel($this->conn);
        $this->metodoPagoUsuarioModel = new MetodoPagoUsuarioModel($this->conn);
    }

    private function ensureSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function getUsuarioId(): int {
        return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    }

    private function nombreMetodoPago(int $metodo): string {
        return match ($metodo) {
            1 => 'Efectivo',
            2 => 'Tarjeta debito',
            3 => 'Tarjeta credito',
            4 => 'Transferencia bancaria',
            default => 'Registrado'
        };
    }

    private function limpiarSesionCheckout(): void {
        unset(
            $_SESSION['carrito'],
            $_SESSION['carrito_mapa_cache'],
            $_SESSION['checkout_direccion_id'],
            $_SESSION['checkout_direccion_snapshot'],
            $_SESSION['checkout_fecha_estimada_entrega'],
            $_SESSION['checkout_resumen'],
            $_SESSION['payment_old']
        );
    }

    private function resumenCompra(array $items): array {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float) ($item['precio'] ?? 0) * (int) ($item['cantidad'] ?? 0);
        }

        $subtotal = round(max(0, $subtotal), 2);
        $iva = round($subtotal * 0.19, 2);
        $envio = 0;

        if (isset($_SESSION['checkout_resumen']) && is_array($_SESSION['checkout_resumen'])) {
            $envio = max(0, (float) ($_SESSION['checkout_resumen']['envio'] ?? 0));
        }

        return [
            'subtotal' => $subtotal,
            'iva' => $iva,
            'envio' => $envio,
            'total' => round($subtotal + $iva + $envio, 2)
        ];
    }

    private function validarItemsCheckout(array $items): void {
        if (empty($items)) {
            throw new Exception('Tu carrito esta vacio');
        }

        foreach ($items as $item) {
            $idProducto = (int) ($item['id_producto'] ?? 0);
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $stock = (int) ($item['stock_p'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);

            if ($idProducto <= 0 || $cantidad <= 0 || $precio < 0) {
                throw new Exception('El carrito contiene productos invalidos');
            }

            if ((int) ($item['id_referencia'] ?? 0) <= 0) {
                throw new Exception('El carrito contiene referencias invalidas');
            }

            if ($stock < $cantidad) {
                throw new Exception('Stock insuficiente para la referencia ' . (int) ($item['id_referencia'] ?? 0));
            }
        }
    }

    private function itemsConfirmacion(array $items): array {
        return array_map(function ($item) {
            $cantidad = (int) ($item['cantidad'] ?? 0);
            $precio = (float) ($item['precio'] ?? 0);

            return [
                'id_producto' => (int) ($item['id_producto'] ?? 0),
                'id_referencia' => (int) ($item['id_referencia'] ?? 0),
                'numero_referencia' => (string) ($item['numero_referencia'] ?? ''),
                'nombre' => (string) ($item['nombre'] ?? 'Producto'),
                'cantidad' => $cantidad,
                'precio' => $precio,
                'subtotal' => round($cantidad * $precio, 2)
            ];
        }, $items);
    }

    private function receptorConfirmacion(array $direccion): array {
        return [
            'nombre' => trim((string) (($direccion['nombre_receptor'] ?? '') . ' ' . ($direccion['apellido_receptor'] ?? ''))),
            'direccion' => (string) ($direccion['direccion_envio'] ?? ''),
            'ciudad' => (string) ($direccion['ciudad'] ?? ''),
            'telefono' => (string) ($direccion['telefono_receptor'] ?? '')
        ];
    }

    private function detectarFranquicia(string $numero): string {
        $numero = preg_replace('/\D+/', '', $numero);
        if (preg_match('/^4\d{12}(\d{3})?(\d{3})?$/', $numero)) {
            return 'VISA';
        }
        if (preg_match('/^(5[1-5]\d{14}|2(2[2-9]\d{12}|[3-6]\d{13}|7[01]\d{12}|720\d{12}))$/', $numero)) {
            return 'MASTERCARD';
        }
        return 'DESCONOCIDA';
    }

    private function fechaExpiracionIso(string $vencimiento): string {
        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', trim($vencimiento), $matches)) {
            throw new InvalidArgumentException('Ingresa el vencimiento en formato MM/AA');
        }

        $mes = (int) $matches[1];
        $anio = 2000 + (int) $matches[2];
        return sprintf('%04d-%02d-01', $anio, $mes);
    }

    private function generarTokenPago(): string {
        try {
            return 'tok_' . bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            return 'tok_' . hash('sha256', uniqid('', true) . microtime(true));
        }
    }

    private function validarPagoEntrada(int $idUsuario, int &$idMetodo, array $data): ?array {
        if (!in_array($idMetodo, [1, 2, 3, 4], true)) {
            throw new InvalidArgumentException('Selecciona un metodo de pago valido');
        }

        if ($idMetodo === 1) {
            if (trim((string) ($data['nombre_pagador'] ?? '')) === '' || trim((string) ($data['documento_pagador'] ?? '')) === '') {
                throw new InvalidArgumentException('Completa los datos del pago en efectivo');
            }
            return null;
        }

        if ($idMetodo === 4) {
            if (trim((string) ($data['banco_origen'] ?? '')) === '' || trim((string) ($data['referencia_transferencia'] ?? '')) === '') {
                throw new InvalidArgumentException('Completa los datos de transferencia');
            }
            return null;
        }

        $idMetodoGuardado = (int) ($data['id_metodo_pago_usuario'] ?? 0);
        $cvv = preg_replace('/\D+/', '', (string) ($data['cvv_tarjeta'] ?? ''));
        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            throw new InvalidArgumentException('Ingresa un CVV valido');
        }

        if ($idMetodoGuardado > 0) {
            $metodoGuardado = $this->metodoPagoUsuarioModel->obtenerPorIdUsuario($idMetodoGuardado, $idUsuario);
            if (!$metodoGuardado) {
                throw new InvalidArgumentException('La tarjeta guardada seleccionada no esta disponible');
            }
            $idMetodo = (int) ($metodoGuardado['id_metodo'] ?? $idMetodo);
            return null;
        }

        $numero = preg_replace('/\D+/', '', (string) ($data['numero_tarjeta'] ?? ''));
        $titular = trim((string) ($data['titular_tarjeta'] ?? ''));
        $vencimiento = trim((string) ($data['vencimiento_tarjeta'] ?? ''));
        $franquicia = $this->detectarFranquicia($numero);

        if (strlen($numero) < 13 || strlen($numero) > 19 || $franquicia === 'DESCONOCIDA') {
            throw new InvalidArgumentException('Ingresa una tarjeta Visa o Mastercard valida');
        }
        if ($titular === '') {
            throw new InvalidArgumentException('Ingresa el nombre del titular');
        }

        $fechaExpiracion = $this->fechaExpiracionIso($vencimiento);
        if (strtotime($fechaExpiracion . ' +1 month -1 day') < strtotime(date('Y-m-d'))) {
            throw new InvalidArgumentException('La tarjeta esta vencida');
        }

        if (empty($data['guardar_metodo_pago'])) {
            return null;
        }

        return [
            'id_usuario' => $idUsuario,
            'id_metodo' => $idMetodo,
            'titular' => $titular,
            'ultimos_4' => substr($numero, -4),
            'franquicia' => $franquicia,
            'token_pago' => $this->generarTokenPago(),
            'fecha_expiracion' => $fechaExpiracion,
            'es_predeterminado' => !empty($data['es_predeterminado_pago']) ? 1 : 0
        ];
    }

    public function confirmarPedido(): void {
        $this->ensureSession();

        $idUsuario = $this->getUsuarioId();
        if ($idUsuario <= 0) {
            $_SESSION['error'] = 'Debes iniciar sesion para finalizar el pago';
            header('Location: index.php?action=login');
            exit();
        }

        $idMetodo = (int) ($_POST['metodo_pago'] ?? 0);

        $idDireccion = (int) ($_SESSION['checkout_direccion_id'] ?? 0);
        if ($idDireccion <= 0) {
            $_SESSION['error'] = 'Selecciona una direccion antes de pagar';
            header('Location: index.php?action=ConfirmarPedido');
            exit();
        }

        $direccion = $this->direccionPedidoModel->obtenerDireccionPorId($idDireccion, $idUsuario);
        if (!$direccion) {
            unset($_SESSION['checkout_direccion_id'], $_SESSION['checkout_direccion_snapshot']);
            $_SESSION['error'] = 'La direccion seleccionada no esta disponible';
            header('Location: index.php?action=ConfirmarPedido');
            exit();
        }

        try {
            $metodoParaGuardar = $this->validarPagoEntrada($idUsuario, $idMetodo, $_POST);
            $items = $this->carritoModel->obtenerItemsCheckoutTx($idUsuario);
            $this->validarItemsCheckout($items);
            $resumen = $this->resumenCompra($items);
            $fechaEstimada = $_SESSION['checkout_fecha_estimada_entrega'] ?? null;

            if ($metodoParaGuardar !== null) {
                $this->metodoPagoUsuarioModel->guardar($metodoParaGuardar);
            }

            $resultadoPedido = $this->pedidoModel->crearPedidoCompletoTx(
                $idUsuario,
                $idDireccion,
                $idMetodo,
                $fechaEstimada,
                $resumen
            );

            $idPedido = (int) ($resultadoPedido['id_pedido'] ?? 0);
            $idVenta = (int) ($resultadoPedido['id_venta'] ?? 0);
            $pedido = $idPedido > 0 ? $this->pedidoModel->obtenerPorId($idPedido) : ($idVenta > 0 ? $this->pedidoModel->obtenerPorVenta($idVenta) : null);
            if (!$pedido) {
                throw new Exception('No se encontro el pedido creado por SP_CREAR_PEDIDO_COMPLETO');
            }
            $idPedido = (int) ($pedido['id_pedido'] ?? $idPedido);
            $idVenta = (int) ($pedido['id_venta'] ?? $idVenta);

            if ($idVenta > 0) {
                $this->pagoModel->procesarPago($idVenta, $idMetodo, $resumen['total']);
            }

            $this->carritoModel->eliminarSeleccionadosTx($idUsuario);

            oci_commit($this->conn);
            $this->limpiarSesionCheckout();
            $_SESSION['carrito_count'] = array_sum($this->carritoModel->obtenerMapaCarritoUsuario($idUsuario));

            $_SESSION['pedido_confirmado'] = [
                'id_pedido' => (int) $pedido['id_pedido'],
                'id_venta' => $idVenta,
                'fecha' => date('Y-m-d H:i:s'),
                'total' => $resumen['total'],
                'subtotal' => $resumen['subtotal'],
                'iva' => $resumen['iva'],
                'envio' => $resumen['envio'],
                'fecha_estimada_entrega' => $pedido['fecha_estimada_entrega'] ?? null,
                'metodo_pago' => $this->nombreMetodoPago($idMetodo),
                'items' => $this->itemsConfirmacion($items),
                'receptor' => $this->receptorConfirmacion($direccion)
            ];

            header('Location: index.php?action=confirmacionPedido');
            exit();
        } catch (Throwable $e) {
            oci_rollback($this->conn);
            error_log($e->getMessage());

            $_SESSION['payment_old'] = [
                'metodo_pago' => $idMetodo > 0 ? $idMetodo : 1,
                'id_metodo_pago_usuario' => (int) ($_POST['id_metodo_pago_usuario'] ?? 0),
                'titular_tarjeta' => trim((string) ($_POST['titular_tarjeta'] ?? '')),
                'vencimiento_tarjeta' => trim((string) ($_POST['vencimiento_tarjeta'] ?? '')),
                'nombre_pagador' => trim((string) ($_POST['nombre_pagador'] ?? '')),
                'documento_pagador' => trim((string) ($_POST['documento_pagador'] ?? '')),
                'banco_origen' => trim((string) ($_POST['banco_origen'] ?? '')),
                'referencia_transferencia' => trim((string) ($_POST['referencia_transferencia'] ?? ''))
            ];
            $_SESSION['error'] = $e instanceof InvalidArgumentException ? $e->getMessage() : 'No se pudo procesar el pago. Verifica la informacion e intenta de nuevo.';
            header('Location: index.php?action=pago');
            exit();
        }
    }
}
