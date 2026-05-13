<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';

class LoginController
{
    public function iniciarSesion(): void
    {
        $carritoInvitado = (isset($_SESSION['carrito']) && is_array($_SESSION['carrito']))
            ? $_SESSION['carrito']
            : [];

        $username = strtolower(trim($_POST['nickname'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            redirectTo('login', 'Complete todos los campos');
        }

        $conn  = Database::getConnection();
        $model = new UsuarioModel($conn);

        try {
            $usuario = $model->validarCredenciales($username, $password);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            redirectTo('login', 'No se pudo iniciar sesion. Intenta de nuevo.');
        }

        if (!$usuario) {
            redirectTo('login', 'Credenciales incorrectas');
        }

        if (strtoupper(trim($usuario['estado'])) !== 'ACTIVO') {
            redirectTo('login', 'Tu cuenta esta inactiva.');
        }

        $carritoModel    = new CarritoModel($conn);
        $resultadoFusion = $carritoModel->fusionarCarritoSesion((int) $usuario['id_usuario'], $carritoInvitado);

        if (($resultadoFusion['success'] ?? false) === false) {
            redirectTo('login', $resultadoFusion['message'] ?? 'No se pudo sincronizar el carrito');
        }

        unset($_SESSION['carrito'], $_SESSION['carrito_count'], $_SESSION['carrito_mapa_cache']);

        $_SESSION['id_usuario']   = $usuario['id_usuario'];
        $_SESSION['username']     = $usuario['username'];
        $_SESSION['nickname']     = $usuario['username'];
        $_SESSION['tipo_usuario'] = $usuario['id_tipo'];
        $_SESSION['usuario']      = [
            'id_usuario' => (int) $usuario['id_usuario'],
            'id_persona' => (int) $usuario['id_persona'],
            'username'   => $usuario['username'],
            'id_tipo'    => (int) $usuario['id_tipo'],
        ];
        $_SESSION['logueado']       = true;
        $_SESSION['bienvenida']     = 'Bienvenido, ' . $usuario['username'];
        $_SESSION['carrito_count']  = $carritoModel->obtenerTotalItemsCarrito((int) $usuario['id_usuario']);

        $destino = ($usuario['id_tipo'] == 1) ? 'admin_panel' : 'tienda';
        header("Location: index.php?action={$destino}");
        exit();
    }

    public function logout(): void
    {
        destroySession();
        header('Location: index.php?action=login');
        exit();
    }
}
