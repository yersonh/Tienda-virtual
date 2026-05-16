<?php
// Controllers/NotificacionController.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

class NotificacionController {

    private $conn;
    private NotificacionModel $model;

    public function __construct() {
        $this->conn  = Database::getConnection();
        $this->model = new NotificacionModel($this->conn);
    }

    private function userId(): int {
        return (int) ($_SESSION['id_usuario'] ?? 0);
    }

    private function json(array $data, int $code = 200): never {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    private function limpiarCacheNavbar(): void {
        unset($_SESSION['user_nav_notif_cache'], $_SESSION['admin_nav_cache']);
    }

    /** GET ?action=notificaciones_json — devuelve lista + contador no leídas */
    public function listarJson(): void {
        $idUsuario = $this->userId();
        if ($idUsuario <= 0) {
            $this->json(['ok' => false, 'items' => [], 'no_leidas' => 0]);
        }
        try {
            $items    = $this->model->obtenerParaUsuario($idUsuario, 15);
            $noLeidas = $this->model->contarNoLeidas($idUsuario);
            $_SESSION['user_nav_notif_cache'] = [
                'ts' => time(),
                'user_id' => $idUsuario,
                'notif' => $noLeidas,
            ];
            
            $devPendientes = 0;
            if (isset($_SESSION['tipo_usuario']) && (int)$_SESSION['tipo_usuario'] === 1) {
                require_once __DIR__ . '/../models/DevolucionModel.php';
                $devPendientes = (new DevolucionModel($this->conn))->contarPendientesAdmin();
                unset($_SESSION['admin_nav_cache']);
            }

            $this->json([
                'ok' => true, 
                'items' => $items, 
                'no_leidas' => $noLeidas,
                'dev_pendientes' => $devPendientes
            ]);
        } catch (Throwable $e) {
            error_log('NotificacionController::listarJson – ' . $e->getMessage());
            $this->json(['ok' => false, 'items' => [], 'no_leidas' => 0, 'dev_pendientes' => 0]);
        }
    }

    /** POST ?action=marcarNotificacionesLeidas — marca todas leídas, retorna JSON */
    public function marcarLeidas(): void {
        $idUsuario = $this->userId();
        if ($idUsuario <= 0) {
            $this->json(['ok' => false]);
        }
        try {
            $this->model->marcarTodas($idUsuario);
            oci_commit($this->conn);
            $this->limpiarCacheNavbar();
            $this->json(['ok' => true]);
        } catch (Throwable $e) {
            error_log('NotificacionController::marcarLeidas – ' . $e->getMessage());
            $this->json(['ok' => false]);
        }
    }
}
