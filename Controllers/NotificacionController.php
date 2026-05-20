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

    private function contadoresNavbar(int $idUsuario): array {
        $now = time();
        $isAdmin = isset($_SESSION['tipo_usuario']) && (int) $_SESSION['tipo_usuario'] === 1;
        $cache = $_SESSION['navbar_counter_cache'] ?? null;

        if (
            is_array($cache)
            && (int) ($cache['user_id'] ?? 0) === $idUsuario
            && (bool) ($cache['is_admin'] ?? false) === $isAdmin
            && (int) ($cache['expires'] ?? 0) >= $now
        ) {
            return [
                'no_leidas' => (int) ($cache['no_leidas'] ?? 0),
                'dev_pendientes' => (int) ($cache['dev_pendientes'] ?? 0)
            ];
        }

        $noLeidas = $this->model->contarNoLeidas($idUsuario);
        $devPendientes = 0;
        if ($isAdmin) {
            require_once __DIR__ . '/../models/DevolucionModel.php';
            $devPendientes = (new DevolucionModel($this->conn))->contarPendientesAdmin();
        }

        $_SESSION['navbar_counter_cache'] = [
            'expires' => $now + 20,
            'user_id' => $idUsuario,
            'is_admin' => $isAdmin,
            'no_leidas' => $noLeidas,
            'dev_pendientes' => $devPendientes
        ];

        return [
            'no_leidas' => $noLeidas,
            'dev_pendientes' => $devPendientes
        ];
    }

    /** GET ?action=notificaciones_json — devuelve lista + contador no leídas */
    public function listarJson(): void {
        $idUsuario = $this->userId();
        if ($idUsuario <= 0) {
            $this->json(['ok' => false, 'items' => [], 'no_leidas' => 0]);
        }
        try {
            if (($_GET['modo'] ?? '') === 'contador') {
                $contadores = $this->contadoresNavbar($idUsuario);
                $this->json([
                    'ok' => true,
                    'items' => [],
                    'no_leidas' => $contadores['no_leidas'],
                    'dev_pendientes' => $contadores['dev_pendientes']
                ]);
            }

            $items    = $this->model->obtenerParaUsuario($idUsuario, 15);
            $contadores = $this->contadoresNavbar($idUsuario);
            $noLeidas = $contadores['no_leidas'];
            $_SESSION['user_nav_notif_cache'] = [
                'ts' => time(),
                'user_id' => $idUsuario,
                'notif' => $noLeidas,
            ];
            
            $devPendientes = $contadores['dev_pendientes'];

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
            unset($_SESSION['navbar_counter_cache']);
            $this->json(['ok' => true]);
        } catch (Throwable $e) {
            error_log('NotificacionController::marcarLeidas – ' . $e->getMessage());
            $this->json(['ok' => false]);
        }
    }
}
