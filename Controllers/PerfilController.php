<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/MetodoPagoUsuarioModel.php';

class PerfilController {

    public function verPerfil() {

        if (!isset($_SESSION['id_usuario'])) {
            header("Location: index.php?action=login");
            exit();
        }

        $conn = Database::getConnection();
        $model = new UsuarioModel($conn);
        $metodoPagoUsuarioModel = new MetodoPagoUsuarioModel($conn);

        $usuario = $model->obtenerPorId($_SESSION['id_usuario']);
        $metodosPagoUsuario = $metodoPagoUsuarioModel->obtenerPorUsuario((int) $_SESSION['id_usuario'], 1);

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
}
