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

        $pdo = Database::getConnection();
        $model = new UsuarioModel($pdo);

        $data = [
            'cc' => $_POST['cc'],
            'nombres' => $_POST['nombres'],
            'apellidos' => $_POST['apellidos'],
            'correo' => $_POST['correo'],
            'telefono' => $_POST['telefono'],
            'direccion' => $_POST['direccion'],
        ];

        $model->actualizarPerfil($_SESSION['id_usuario'], $data);

        $_SESSION['success'] = "Datos actualizados correctamente";
        header("Location: index.php?action=perfil");
    }
}