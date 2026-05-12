<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DevolucionModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';
require_once __DIR__ . '/../services/DevolucionService.php';
require_once __DIR__ . '/../services/ReembolsoService.php';
require_once __DIR__ . '/../services/ReacondicionadoService.php';
require_once __DIR__ . '/../middleware/Auth.php';

class DevolucionController {

    private $conn;
    private DevolucionModel        $model;
    private DevolucionService      $devService;
    private ReembolsoService       $reembolsoService;
    private ReacondicionadoService $reacService;
    private NotificacionModel      $notifModel;

    public function __construct() {
        $this->conn             = Database::getConnection();
        $this->model            = new DevolucionModel($this->conn);
        $this->notifModel       = new NotificacionModel($this->conn);
        $this->devService       = new DevolucionService($this->conn, $this->model);
        $this->reembolsoService = new ReembolsoService($this->conn, $this->model);
        $this->reacService      = new ReacondicionadoService($this->conn, $this->model);
    }

    // ─── UTILIDADES ───────────────────────────────────────────────────────────

    private function userId(): int {
        return (int) ($_SESSION['id_usuario'] ?? 0);
    }

    private function redirect(string $url): never {
        header('Location: ' . $url);
        exit();
    }

    private function requirePost(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->redirect('index.php?action=inicio');
        }
    }

    // ─── USUARIO: SOLICITUD ───────────────────────────────────────────────────

    /** GET ?action=solicitarDevolucion&id_pedido=X */
    public function solicitarForm(): void {
        $idUsuario = $this->userId();
        $idPedido  = (int) ($_GET['id_pedido'] ?? 0);

        if ($idPedido <= 0) {
            $_SESSION['error'] = 'Pedido no válido.';
            $this->redirect('index.php?action=misPedidos');
        }

        try {
            $pedido = $this->model->obtenerPedidoParaDevolucion($idPedido, $idUsuario);
            if (!$pedido) {
                $_SESSION['error'] = 'Solo puedes solicitar devolución en pedidos entregados.';
                $this->redirect('index.php?action=misPedidos');
            }

            if ($this->model->existeDevolucionActivaPorPedido($idPedido, $idUsuario)) {
                $_SESSION['error'] = 'Ya existe una solicitud de devolución activa para este pedido.';
                $this->redirect('index.php?action=misDevoluciones');
            }

            $items = $this->model->obtenerItemsPedidoParaDevolucion($idPedido, $idUsuario);
            if (empty($items)) {
                $_SESSION['error'] = 'No se encontraron productos en este pedido.';
                $this->redirect('index.php?action=misPedidos');
            }

            require_once __DIR__ . '/../views/devoluciones/solicitar.php';
        } catch (Throwable $e) {
            error_log('DevolucionController::solicitarForm – ' . $e->getMessage());
            $_SESSION['error'] = 'Ocurrió un error al cargar el formulario de devolución.';
            $this->redirect('index.php?action=misPedidos');
        }
    }

    /** POST ?action=enviarDevolucion */
    public function enviarSolicitud(): void {
        $this->requirePost();
        $idUsuario = $this->userId();
        $idPedido  = (int) ($_POST['id_pedido'] ?? 0);

        if ($idPedido <= 0) {
            $_SESSION['error'] = 'Pedido no válido.';
            $this->redirect('index.php?action=misPedidos');
        }

        try {
            $this->devService->crearSolicitud($idPedido, $idUsuario, $_POST, $_FILES);
            // Notificar al administrador
            try {
                $idAdmin = $this->notifModel->obtenerIdAdmin();
                if ($idAdmin !== null) {
                    $this->notifModel->crear(
                        $idAdmin,
                        'Nueva solicitud de devolución',
                        'Se recibió una nueva solicitud de devolución para el Pedido #' . $idPedido . '.',
                        'warning',
                        'index.php?action=admin_devoluciones'
                    );
                    oci_commit($this->conn); // Commit para la notificación
                }
            } catch (Throwable $eNotif) { 
                error_log('DevolucionController::enviarSolicitud (Notif Admin) – ' . $eNotif->getMessage());
            }
            $_SESSION['success'] = 'Solicitud de devolución enviada. El equipo revisará tu caso.';
            $this->redirect('index.php?action=misDevoluciones');
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('DevolucionController::enviarSolicitud – ' . $e->getMessage());
            $_SESSION['error'] = $e->getMessage() ?: 'No se pudo registrar la devolución. Intenta de nuevo.';
            $this->redirect('index.php?action=solicitarDevolucion&id_pedido=' . $idPedido);
        }
    }

    // ─── USUARIO: VER ─────────────────────────────────────────────────────────

    /** GET ?action=misDevoluciones */
    public function misDevoluciones(): void {
        $idUsuario = $this->userId();
        $idEstado  = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int) $_GET['estado'] : null;
        try {
            $devoluciones = $this->model->obtenerDevolucionesPorUsuario($idUsuario, $idEstado);
            $estados      = $this->model->obtenerEstados();
        } catch (Throwable $e) {
            error_log('DevolucionController::misDevoluciones – ' . $e->getMessage());
            $devoluciones = [];
            $estados      = [];
        }
        require_once __DIR__ . '/../views/devoluciones/mis_devoluciones.php';
    }

    /** GET ?action=devolucionDetalle&id=X */
    public function verDetalle(): void {
        $idUsuario    = $this->userId();
        $idDevolucion = (int) ($_GET['id'] ?? 0);

        if ($idDevolucion <= 0) {
            $_SESSION['error'] = 'Devolución no válida.';
            $this->redirect('index.php?action=misDevoluciones');
        }

        try {
            $devolucion = $this->model->obtenerDevolucionPorId($idDevolucion, $idUsuario);
            if (!$devolucion) {
                $_SESSION['error'] = 'No se encontró la devolución.';
                $this->redirect('index.php?action=misDevoluciones');
            }
        } catch (Throwable $e) {
            error_log('DevolucionController::verDetalle – ' . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la devolución.';
            $this->redirect('index.php?action=misDevoluciones');
        }
        require_once __DIR__ . '/../views/devoluciones/detalle.php';
    }

    // ─── PÚBLICA: OFERTAS REACONDICIONADAS ───────────────────────────────────

    /** GET ?action=reacondicionados */
    public function ofertasPublicas(): void {
        try {
            $items = $this->model->obtenerOfertasReacondicionadas();
        } catch (Throwable $e) {
            error_log('DevolucionController::ofertasPublicas – ' . $e->getMessage());
            $items = [];
        }
        require_once __DIR__ . '/../views/tienda/reacondicionados.php';
    }

    // ─── ADMIN: DEVOLUCIONES ──────────────────────────────────────────────────

    /** GET ?action=admin_devoluciones */
    public function adminIndex(): void {
        Auth::soloAdmin();
        $idEstadoFiltro = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int) $_GET['estado'] : null;
        try {
            $devoluciones = $this->model->obtenerDevolucionesAdmin($idEstadoFiltro);
            $estados      = $this->model->obtenerEstados();
        } catch (Throwable $e) {
            error_log('DevolucionController::adminIndex – ' . $e->getMessage());
            $devoluciones = [];
            $estados      = [];
        }
        ob_start();
        require_once __DIR__ . '/../views/admin/devoluciones/index.php';
        $contenido = ob_get_clean();
        require_once __DIR__ . '/../views/admin/nav.php';
    }

    /** GET ?action=admin_devolucion_detalle&id=X */
    public function adminDetalle(): void {
        Auth::soloAdmin();
        $idDevolucion = (int) ($_GET['id'] ?? 0);
        if ($idDevolucion <= 0) {
            $_SESSION['error'] = 'ID de devolución no válido.';
            $this->redirect('index.php?action=admin_devoluciones');
        }
        try {
            $devolucion = $this->model->obtenerDevolucionPorId($idDevolucion);
            if (!$devolucion) {
                $_SESSION['error'] = 'Devolución no encontrada.';
                $this->redirect('index.php?action=admin_devoluciones');
            }
        } catch (Throwable $e) {
            error_log('DevolucionController::adminDetalle – ' . $e->getMessage());
            $_SESSION['error'] = 'Error al cargar la devolución.';
            $this->redirect('index.php?action=admin_devoluciones');
        }
        ob_start();
        require_once __DIR__ . '/../views/admin/devoluciones/detalle.php';
        $contenido = ob_get_clean();
        require_once __DIR__ . '/../views/admin/nav.php';
    }

    /** POST ?action=admin_aprobar_devolucion */
    public function adminAprobar(): void {
        Auth::soloAdmin();
        $this->requirePost();

        $idDevolucion    = (int)    ($_POST['id_devolucion']   ?? 0);
        $observacionAdmin = trim((string) ($_POST['observacion_admin'] ?? ''));

        $cantidades = [];
        foreach ($_POST as $key => $val) {
            if (str_starts_with($key, 'cantidad_aprobada_')) {
                $idDet = (int) substr($key, strlen('cantidad_aprobada_'));
                if ($idDet > 0) {
                    $cantidades[$idDet] = max(0, (int) $val);
                }
            }
        }

        try {
            $this->model->aprobarDevolucion($idDevolucion, $cantidades, $observacionAdmin);
            oci_commit($this->conn);
            // Notificar al cliente
            try {
                $devData = $this->model->obtenerDevolucionPorId($idDevolucion);
                if ($devData) {
                    $this->notifModel->crear(
                        (int) $devData['id_usuario'],
                        'Devolución aprobada',
                        'Tu solicitud de devolución #' . $idDevolucion . ' ha sido aprobada. Por favor, envía los productos.',
                        'success',
                        'index.php?action=devolucionDetalle&id=' . $idDevolucion
                    );
                    oci_commit($this->conn); // Commit para la notificación
                }
            } catch (Throwable $eNotif) { 
                error_log('DevolucionController::adminAprobar (Notif Cliente) – ' . $eNotif->getMessage());
            }
            $_SESSION['success'] = 'Devolución aprobada. El cliente debe enviar los productos.';
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('DevolucionController::adminAprobar – ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo aprobar: ' . $e->getMessage();
        }
        $this->redirect('index.php?action=admin_devolucion_detalle&id=' . $idDevolucion);
    }

    /** POST ?action=admin_rechazar_devolucion */
    public function adminRechazar(): void {
        Auth::soloAdmin();
        $this->requirePost();

        $idDevolucion = (int)    ($_POST['id_devolucion']  ?? 0);
        $motivo       = trim((string) ($_POST['motivo_rechazo'] ?? ''));

        if ($motivo === '') {
            $_SESSION['error'] = 'Debes indicar el motivo del rechazo.';
            $this->redirect('index.php?action=admin_devolucion_detalle&id=' . $idDevolucion);
        }

        try {
            $this->model->rechazarDevolucion($idDevolucion, $motivo);
            oci_commit($this->conn);
            // Notificar al cliente
            try {
                $devData = $this->model->obtenerDevolucionPorId($idDevolucion);
                if ($devData) {
                    $this->notifModel->crear(
                        (int) $devData['id_usuario'],
                        'Solicitud de devolución rechazada',
                        'Tu solicitud #' . $idDevolucion . ' no fue aprobada. Motivo: ' . mb_substr($motivo, 0, 80),
                        'error',
                        'index.php?action=devolucionDetalle&id=' . $idDevolucion
                    );
                    oci_commit($this->conn); // Commit para la notificación
                }
            } catch (Throwable $eNotif) { 
                error_log('DevolucionController::adminRechazar (Notif Cliente) – ' . $eNotif->getMessage());
            }
            $_SESSION['success'] = 'Devolución rechazada.';
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('DevolucionController::adminRechazar – ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo rechazar: ' . $e->getMessage();
        }
        $this->redirect('index.php?action=admin_devolucion_detalle&id=' . $idDevolucion);
    }

    /**
     * POST ?action=admin_producto_recibido
     *
     * Flujo separado (estado físico vs financiero):
     *  1. SP_PRODUCTO_RECIBIDO → actualiza DEVOLUCION_DETALLE + crea INVENTARIO_REACONDICIONADO
     *  2. ReembolsoService::procesarReembolso → Wompi + registra estado en DEVOLUCION
     *  3. Commit
     */
    public function adminProductoRecibido(): void {
        Auth::soloAdmin();
        $this->requirePost();

        $idDevolucionDetalle = (int) ($_POST['id_devolucion_detalle'] ?? 0);
        $idDevolucion        = (int) ($_POST['id_devolucion']         ?? 0);

        if ($idDevolucionDetalle <= 0 || $idDevolucion <= 0) {
            $_SESSION['error'] = 'Datos inválidos.';
            $this->redirect('index.php?action=admin_devolucion_detalle&id=' . $idDevolucion);
        }

        try {
            $devolucion = $this->model->obtenerDevolucionPorId($idDevolucion);
            if (!$devolucion) {
                throw new Exception('Devolución no encontrada.');
            }

            $detalle = null;
            foreach ($devolucion['detalles'] as $det) {
                if ((int) $det['id_devolucion_detalle'] === $idDevolucionDetalle) {
                    $detalle = $det;
                    break;
                }
            }
            if (!$detalle) {
                throw new Exception('Detalle de devolución no encontrado.');
            }

            // 1. Estado físico: SP actualiza DEVOLUCION_DETALLE y crea INVENTARIO_REACONDICIONADO
            $this->model->marcarProductoRecibido($idDevolucionDetalle);

            // 2. Estado financiero: llamada Wompi separada
            $estadoReembolso = $this->reembolsoService->procesarReembolso($idDevolucion, $detalle);

            oci_commit($this->conn);

            $msg = 'Producto marcado como recibido y movido al inventario reacondicionado.';
            if ($estadoReembolso === 'PENDIENTE-MANUAL') {
                $msg .= ' ATENCIÓN: el reembolso Wompi falló – emite el reembolso manualmente desde el panel de Wompi.';
            } elseif ($estadoReembolso === 'REALIZADO') {
                $devActualizada = $this->model->obtenerDevolucionPorId($idDevolucion);
                $wompiId = $devActualizada['reembolso_id_wompi'] ?? '';
                if ($wompiId !== '') {
                    $msg .= ' Reembolso Wompi procesado (ID: ' . htmlspecialchars($wompiId, ENT_QUOTES, 'UTF-8') . ').';
                }
            }
            $_SESSION['success'] = $msg;
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('DevolucionController::adminProductoRecibido – ' . $e->getMessage());
            $_SESSION['error'] = 'Error al procesar: ' . $e->getMessage();
        }

        $this->redirect('index.php?action=admin_devolucion_detalle&id=' . $idDevolucion);
    }

    // ─── ADMIN: INVENTARIO REACONDICIONADO ───────────────────────────────────

    /** GET ?action=admin_reacondicionados */
    public function adminInventarioReacondicionado(): void {
        Auth::soloAdmin();
        try {
            $itemsPendientes = $this->model->obtenerInventarioReacondicionado('PENDIENTE');
            $itemsOferta10   = $this->model->obtenerInventarioReacondicionado('OFERTA_10');
            $itemsOferta15   = $this->model->obtenerInventarioReacondicionado('OFERTA_15');
        } catch (Throwable $e) {
            error_log('DevolucionController::adminInventarioReacondicionado – ' . $e->getMessage());
            $itemsPendientes = $itemsOferta10 = $itemsOferta15 = [];
        }
        ob_start();
        require_once __DIR__ . '/../views/admin/reacondicionados/index.php';
        $contenido = ob_get_clean();
        require_once __DIR__ . '/../views/admin/nav.php';
    }

    /** GET ?action=admin_stock_muerto */
    public function adminStockMuerto(): void {
        Auth::soloAdmin();
        try {
            $items = $this->model->obtenerStockMuerto();
        } catch (Throwable $e) {
            error_log('DevolucionController::adminStockMuerto – ' . $e->getMessage());
            $items = [];
        }
        ob_start();
        require_once __DIR__ . '/../views/admin/reacondicionados/stock_muerto.php';
        $contenido = ob_get_clean();
        require_once __DIR__ . '/../views/admin/nav.php';
    }

    /** POST ?action=admin_ofertar_reacondicionado */
    public function adminOfertarItem(): void {
        Auth::soloAdmin();
        $this->requirePost();
        $idItem = (int) ($_POST['id_item'] ?? 0);
        try {
            $this->reacService->ofertar($idItem);
            $_SESSION['success'] = 'Producto publicado con 10% de descuento.';
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        $this->redirect('index.php?action=admin_reacondicionados');
    }

    /** POST ?action=admin_reofertar_reacondicionado */
    public function adminReofertarItem(): void {
        Auth::soloAdmin();
        $this->requirePost();
        $idItem = (int) ($_POST['id_item'] ?? 0);
        try {
            $this->reacService->reofertar($idItem);
            $_SESSION['success'] = 'Producto reofertado con 15% de descuento.';
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        $this->redirect('index.php?action=admin_reacondicionados');
    }

    /** POST ?action=admin_sacar_inventario */
    public function adminSacarInventario(): void {
        Auth::soloAdmin();
        $this->requirePost();
        $idItem = (int) ($_POST['id_item'] ?? 0);
        try {
            $this->reacService->sacarInventario($idItem);
            $_SESSION['success'] = 'Producto movido a Stock Muerto.';
        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        $this->redirect('index.php?action=admin_reacondicionados');
    }
    /**
     * POST ?action=admin_reintentar_reembolso
     * Permite reintentar el reembolso Wompi si falló inicialmente.
     */
    public function adminReintentarReembolso(): void {
        Auth::soloAdmin();
        $this->requirePost();

        $idDevolucion = (int) ($_POST['id_devolucion'] ?? 0);
        if ($idDevolucion <= 0) {
            $this->redirect('index.php?action=admin_devoluciones');
        }

        try {
            $devolucion = $this->model->obtenerDevolucionPorId($idDevolucion);
            if (!$devolucion) {
                throw new Exception('Devolución no encontrada.');
            }

            // Buscamos detalles que estén en estado RECIBIDO o APROBADO pero cuyo reembolso no sea REALIZADO
            $detalles = $devolucion['detalles'] ?? [];
            $exito    = false;
            $errores  = [];

            // Si ya hay un reembolso realizado, no hacemos nada
            if (($devolucion['reembolso_estado'] ?? '') === 'REALIZADO') {
                throw new Exception('El reembolso ya ha sido marcado como REALIZADO.');
            }

            foreach ($detalles as $det) {
                // Reintentamos con el primer detalle que encontremos que amerite reembolso
                // (Normalmente es uno por devolución o se procesa por la devolución entera)
                $estado = $this->reembolsoService->procesarReembolso($idDevolucion, $det);
                if ($estado === 'REALIZADO') {
                    $exito = true;
                    break; 
                } else {
                    $errores[] = "Error: $estado";
                }
            }

            if ($exito) {
                oci_commit($this->conn);
                $_SESSION['success'] = 'Reembolso reintentado exitosamente.';
            } else {
                $_SESSION['error'] = 'No se pudo procesar el reembolso: ' . implode(', ', array_unique($errores));
            }

        } catch (Throwable $e) {
            @oci_rollback($this->conn);
            error_log('DevolucionController::adminReintentarReembolso – ' . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('index.php?action=admin_devolucion_detalle&id=' . $idDevolucion);
    }
}
