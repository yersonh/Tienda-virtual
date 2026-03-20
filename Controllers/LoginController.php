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

        if (!empty($nickname) && !empty($password)) {

            $usuario = $model->verificarCredenciales($nickname, $password);

            if ($usuario) {

                // VALIDAR ESTADO
                if ($usuario['estado'] !== 'Activo') {
                    $_SESSION['error'] = "Usuario inactivo";
                    header("Location: index.php");
                    exit();
                }

                // SESIÓN
                $_SESSION['id_usuario'] = $usuario['id_usuario'];
                $_SESSION['nickname'] = $usuario['username'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

                // REDIRECCIÓN
                switch ($usuario['tipo_usuario']) {
                    case 'Administrador':
                        header('Location: dashboard.php');
                        break;
                    default:
                        header('Location: dashboard.php');
                        break;
                }
                exit();

            } else {
                $_SESSION['error'] = "Credenciales incorrectas";
            }

        } else {
            $_SESSION['error'] = "Complete todos los campos";
        }

        header("Location: index.php");
    }

    public function logout() {
        session_start();
        session_destroy();
        header("Location: index.php?logout=success");
    }
}