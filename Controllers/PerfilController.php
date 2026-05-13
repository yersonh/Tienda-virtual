<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class PerfilController {

    public function verPerfil() {

        if (!isset($_SESSION['id_usuario'])) {
            header("Location: index.php?action=login");
            exit();
        }

        $conn = Database::getConnection();
        $model = new UsuarioModel($conn);

        $usuario = $model->obtenerPorId($_SESSION['id_usuario']);

        require_once __DIR__ . '/../views/Perfil.php';
    }

    public function actualizar() {

        if (!isset($_SESSION['id_usuario'])) {
            header("Location: index.php?action=login");
            exit();
        }

        if (
            empty($_POST['nombres']) ||
            empty($_POST['apellidos']) ||
            empty($_POST['correo']) ||
            empty($_POST['telefono']) ||
            empty($_POST['direccion'])
        ) {
            $_SESSION['error'] = "Todos los campos son obligatorios";
            header("Location: index.php?action=perfil");
            exit();
        }

        $conn = Database::getConnection();
        $model = new UsuarioModel($conn);

        $data = [
            'nombres' => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'correo' => trim($_POST['correo']),
            'telefono' => trim($_POST['telefono']),
            'direccion' => trim($_POST['direccion']),
        ];

        $resultado = $model->actualizarPerfil($_SESSION['id_usuario'], $data);

        if (!$resultado['success']) {
            $_SESSION['error'] = $resultado['message'];
            header("Location: index.php?action=perfil");
            exit();
        }

        $_SESSION['success'] = "Datos actualizados correctamente";
        header("Location: index.php?action=perfil");
        exit();
    }

    public function inactivarCuenta(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['id_usuario'])) {
            header('Location: index.php?action=login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=perfil');
            exit();
        }

        $idUsuario = (int) $_SESSION['id_usuario'];

        $conn  = Database::getConnection();
        $model = new UsuarioModel($conn);

        try {
            $model->inactivarUsuario($idUsuario);
        } catch (Throwable $e) {
            error_log('inactivarCuenta: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo inactivar la cuenta. Intenta de nuevo.';
            header('Location: index.php?action=perfil');
            exit();
        }

        // Destruir sesion actual
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();

        // Nueva sesion solo para el mensaje de confirmacion
        session_start();
        $_SESSION['success'] = 'Tu cuenta fue inactivada correctamente.';

        header('Location: index.php?action=login');
        exit();
    }
}
