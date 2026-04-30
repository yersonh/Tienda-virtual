<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';

class LoginController {

    public function iniciarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $conn = Database::getConnection();
        $model = new UsuarioModel($conn);
        $carritoInvitado = (isset($_SESSION['carrito']) && is_array($_SESSION['carrito']))
            ? $_SESSION['carrito']
            : [];

        $username = strtolower(trim($_POST['nickname'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = "Complete todos los campos";
            header("Location: index.php?action=login");
            exit();
        }

        $usuario = $model->validarCredenciales($username, $password);

        if (!$usuario) {
            $_SESSION['error'] = "Credenciales incorrectas";
            header("Location: index.php?action=login");
            exit();
        }

        if (strtoupper(trim($usuario['estado'])) !== 'ACTIVO') {
            $_SESSION['error'] = "Usuario inactivo";
            header("Location: index.php?action=login");
            exit();
        }

        $carritoModel = new CarritoModel($conn);
        $resultadoFusion = $carritoModel->fusionarCarritoSesion((int) $usuario['id_usuario'], $carritoInvitado);

        if (($resultadoFusion['success'] ?? false) === false) {
            $_SESSION['error'] = $resultadoFusion['message'] ?? 'No se pudo sincronizar el carrito';
            header("Location: index.php?action=login");
            exit();
        }

        unset($_SESSION['carrito'], $_SESSION['carrito_count'], $_SESSION['carrito_mapa_cache']);
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['nickname'] = $usuario['username'];
        $_SESSION['tipo_usuario'] = $usuario['id_tipo'];
        $_SESSION['usuario'] = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'username' => $usuario['username'],
            'id_tipo' => (int) $usuario['id_tipo']
        ];
        $_SESSION['logueado'] = true;
        $_SESSION['bienvenida'] = "Bienvenido, " . $usuario['username'];
        $_SESSION['carrito_count'] = $carritoModel->obtenerTotalItemsCarrito((int) $usuario['id_usuario']);

        if ($usuario['id_tipo'] == 1) {
            header("Location: index.php?action=admin_panel");
            exit();
        }

        if ($usuario['id_tipo'] == 3) {
            header("Location: index.php?action=inicio");
            exit();
        }
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header("Location: index.php?action=login");
        exit();
    }
}
