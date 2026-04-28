<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';

class LoginController {

    public function iniciarSesion() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);

        $nickname = trim($_POST['nickname'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($nickname) || empty($password)) {
            $_SESSION['error'] = "Complete todos los campos";
            header("Location: index.php?action=login");
            exit();
        }

        $usuario = $model->validarCredenciales($nickname, $password);

        if (!$usuario) {
            $_SESSION['error'] = "Credenciales incorrectas";
            header("Location: index.php?action=login");
            exit();
        }

        $estadoActivo = in_array($usuario['estado'], ['Activo', '1', 1, true, 't'], true);
        if (!$estadoActivo) {
            $_SESSION['error'] = "Usuario inactivo";
            header("Location: index.php?action=login");
            exit();
        }

        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nickname'] = $usuario['username'];
        $_SESSION['tipo_usuario'] = $usuario['id_tipo'];
        $_SESSION['bienvenida'] = "👋 Bienvenido, " . $usuario['username'];

        $carritoModel = new CarritoModel($pdo);
        $carritoModel->obtenerOCrearCarritoUsuario((int) $usuario['id_usuario']);
        $carritoInvitado = $_SESSION['carrito'] ?? [];
        $carritoModel->fusionarCarritoInvitado((int) $usuario['id_usuario'], $carritoInvitado);
        unset($_SESSION['carrito']);
        $_SESSION['carrito'] = $carritoModel->obtenerMapaCarritoUsuario((int) $usuario['id_usuario']);

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

        session_destroy();
        header("Location: index.php");
        exit();
    }
} 
