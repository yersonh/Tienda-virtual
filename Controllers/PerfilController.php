<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class PerfilController
{
    public function verPerfil(): void
    {
        requireLogin();

        $conn    = Database::getConnection();
        $model   = new UsuarioModel($conn);
        $usuario = $model->obtenerPorId($_SESSION['id_usuario']);

        require_once __DIR__ . '/../views/Perfil.php';
    }

    public function actualizar(): void
    {
        requireLogin();

        if (
            empty($_POST['nombres'])   ||
            empty($_POST['apellidos']) ||
            empty($_POST['correo'])    ||
            empty($_POST['telefono'])  ||
            empty($_POST['direccion'])
        ) {
            redirectTo('perfil', 'Todos los campos son obligatorios');
        }

        $conn  = Database::getConnection();
        $model = new UsuarioModel($conn);

        $data = [
            'nombres'   => trim($_POST['nombres']),
            'apellidos' => trim($_POST['apellidos']),
            'correo'    => trim($_POST['correo']),
            'telefono'  => trim($_POST['telefono']),
            'direccion' => trim($_POST['direccion']),
        ];

        $resultado = $model->actualizarPerfil($_SESSION['id_usuario'], $data);

        if (!$resultado['success']) {
            redirectTo('perfil', $resultado['message']);
        }

        redirectTo('perfil', 'Datos actualizados correctamente', 'success');
    }

    public function inactivarCuenta(): void
    {
        requireLogin();
        requirePost('perfil');

        $conn  = Database::getConnection();
        $model = new UsuarioModel($conn);

        try {
            $model->inactivarUsuario((int) $_SESSION['id_usuario']);
        } catch (\Throwable $e) {
            error_log('inactivarCuenta: ' . $e->getMessage());
            redirectTo('perfil', 'No se pudo inactivar la cuenta. Intenta de nuevo.');
        }

        destroySession(true);
        redirectTo('login', 'Tu cuenta fue inactivada correctamente.', 'success');
    }
}
