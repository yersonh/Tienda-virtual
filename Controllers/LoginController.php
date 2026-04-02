<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class LoginController {

    public function iniciarSesion() {

        session_start();

        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);

        $nickname = trim($_POST['nickname'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($nickname) || empty($password)) {
            $_SESSION['error'] = "Complete todos los campos";
            header("Location: index.php");
            exit();
        }

        // 🔥 CORREGIDO
        $usuario = $model->validarCredenciales($nickname, $password);

        if (!$usuario) {
            $_SESSION['error'] = "Credenciales incorrectas";
            header("Location: index.php");
            exit();
        }

        if ($usuario['estado'] !== 'Activo') {
            $_SESSION['error'] = "Usuario inactivo";
            header("Location: index.php");
            exit();
        }

        // SESIÓN
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['nickname'] = $usuario['username'];
        $_SESSION['tipo_usuario'] = $usuario['id_tipo'];

        // REDIRECCIÓN
        switch ($usuario['id_tipo']) {
            case 1:
                header('Location: admin_dashboard.php');
                break;
            case 2:
                header('Location: dashboard.php');
                break;
            case 3:
                header('Location: tienda.php');
                break;
            default:
                header('Location: dashboard.php');
        }

        exit();
    }

    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php");
    }
}