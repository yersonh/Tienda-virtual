<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class PerfilController {

    public function verPerfil() {

        if (!isset($_SESSION['id_usuario'])) {
            header("Location: index.php?action=login");
            exit();
        }

        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);

        $usuario = $model->obtenerPorId($_SESSION['id_usuario']);

        require_once __DIR__ . '/../views/Perfil.php';
    }

    public function actualizar() {

        if (!isset($_SESSION['id_usuario'])) {
            header("Location: index.php?action=login");
            exit();
        }

        // 🔥 Validar campos vacíos
        if (
            // empty($_POST['cc']) ||
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

        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);

        $data = [
            // 'cc' => trim($_POST['cc']),
            'nombres' => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'correo' => trim($_POST['correo']),
            'telefono' => trim($_POST['telefono']),
            'direccion' => trim($_POST['direccion']),
        ];

        // 🔥 Ejecutar actualización
        $resultado = $model->actualizarPerfil($_SESSION['id_usuario'], $data);

        // 🔥 Manejo de respuesta del modelo
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