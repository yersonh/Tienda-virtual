<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/CarritoModel.php';

class RegistroController {

    public function registrar() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);

        $data = [
            'nombres' => trim($_POST['nombres'] ?? ''),
            'apellidos' => trim($_POST['apellidos'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'telefono' => trim($_POST['telefono'] ?? ''),
            'direccion' => trim($_POST['direccion'] ?? ''),
            'username' => trim($_POST['username'] ?? ''),
            'password' => trim($_POST['password'] ?? ''),
            'id_tipo' => 3
        ];

        $_SESSION['old'] = $data;

        if (
            empty($data['nombres']) ||
            empty($data['apellidos']) ||
            empty($data['correo']) ||
            empty($data['telefono']) ||
            empty($data['direccion']) ||
            empty($data['username']) ||
            empty($data['password'])
        ) {
            $_SESSION['error'] = "Todos los campos son obligatorios";
            header("Location: index.php?action=registro");
            exit();
        }

        if (strlen($data['password']) < 6) {
            $_SESSION['error'] = "La contrasena debe tener minimo 6 caracteres";
            header("Location: index.php?action=registro");
            exit();
        }

        if (!filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Correo invalido";
            header("Location: index.php?action=registro");
            exit();
        }

        if (!is_numeric($data['telefono']) || strlen($data['telefono']) != 10) {
            $_SESSION['error'] = "El telefono debe tener 10 digitos";
            header("Location: index.php?action=registro");
            exit();
        }

        if ($model->usernameExiste($data['username'])) {
            $_SESSION['error'] = "El usuario ya esta en uso";
            header("Location: index.php?action=registro");
            exit();
        }

        $data['cc'] = null;

        $resultado = $model->crearConPersona($data);

        if ($resultado['success']) {
            $idUsuario = (int) ($resultado['id_usuario'] ?? 0);
            $carritoInvitado = $_SESSION['carrito'] ?? [];

            unset($_SESSION['old']);

            $_SESSION['id_usuario'] = $idUsuario;
            $_SESSION['nickname'] = $data['username'];
            $_SESSION['tipo_usuario'] = 3;
            $_SESSION['bienvenida'] = "Bienvenido, " . $data['username'];

            $carritoModel = new CarritoModel($pdo);
            $carritoModel->fusionarCarritoInvitado($idUsuario, $carritoInvitado);
            $_SESSION['carrito'] = $carritoModel->obtenerMapaCarritoUsuario($idUsuario);

            $_SESSION['success'] = "Registro exitoso";
            header("Location: index.php?action=inicio");
        } else {
            $_SESSION['error'] = "Error al registrar el usuario";
            header("Location: index.php?action=registro");
        }

        exit();
    }
}
