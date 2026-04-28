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

        // 🔥 NORMALIZACIÓN CORRECTA
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

        // 🔥 VALIDACIÓN DE ESTADO LIMPIA
        if ($usuario['estado'] !== 'ACTIVO') {
            $_SESSION['error'] = "Usuario inactivo";
            header("Location: index.php?action=login");
            exit();
        }

        // 🔐 SESIÓN
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['username'] = $usuario['username'];
        $_SESSION['tipo_usuario'] = $usuario['id_tipo'];
        $_SESSION['bienvenida'] = "👋 Bienvenido, " . $usuario['username'];

        // 🛒 CARRITO
        $carritoModel = new CarritoModel($conn);
        $carritoModel->obtenerOCrearCarritoUsuario((int) $usuario['id_usuario']);

        $carritoInvitado = $_SESSION['carrito'] ?? [];
        $carritoModel->fusionarCarritoInvitado((int) $usuario['id_usuario'], $carritoInvitado);

        unset($_SESSION['carrito']);
        $_SESSION['carrito'] = $carritoModel->obtenerMapaCarritoUsuario((int) $usuario['id_usuario']);

        // 🚀 REDIRECCIÓN
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