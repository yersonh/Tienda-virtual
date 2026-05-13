<?php

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../Notifications/mail.php';

class ReactivarController
{
    public function solicitarReactivacion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=reactivar');
            exit;
        }

        $identificador = trim($_POST['identificador'] ?? '');

        if (empty($identificador)) {
            $_SESSION['error'] = 'Por favor, ingresá tu usuario, correo o teléfono.';
            header('Location: index.php?action=reactivar');
            exit;
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->buscarPorIdentificador($identificador);

        if ($usuario && $usuario['ESTADO'] !== 'ACTIVO') {
            try {
                $token = $usuarioModel->generarTokenReactivacion((int) $usuario['ID_USUARIO']);
                $mailer = new Mailer();
                $mailer->enviarReactivacion($usuario['NOMBRES'], $usuario['CORREO'], $token);
            } catch (\Throwable $e) {
                error_log('Error generando token de reactivación: ' . $e->getMessage());
            }
        }

        $_SESSION['success'] = 'Si los datos corresponden a una cuenta inactiva, recibirás un correo con el enlace de reactivación.';
        header('Location: index.php?action=reactivar');
        exit;
    }

    public function confirmarReactivacion()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = trim($_GET['token'] ?? '');

        if (empty($token)) {
            $_SESSION['error'] = 'Enlace inválido.';
            header('Location: index.php?action=reactivar');
            exit;
        }

        $usuarioModel = new UsuarioModel();
        $datos = $usuarioModel->validarTokenReactivacion($token);

        if (!$datos) {
            $_SESSION['error'] = 'El enlace de reactivación es inválido o ya expiró. Solicitá uno nuevo.';
            header('Location: index.php?action=reactivar');
            exit;
        }

        try {
            $usuarioModel->reactivarUsuario((int) $datos['USUARIO_ID']);
            $usuarioModel->marcarTokenReactivacionUsado($token);
            $_SESSION['success'] = 'Tu cuenta ha sido reactivada. Ya podés iniciar sesión.';
        } catch (\Throwable $e) {
            error_log('Error reactivando cuenta: ' . $e->getMessage());
            $_SESSION['error'] = 'No se pudo reactivar la cuenta. Intentá de nuevo.';
        }

        header('Location: index.php?action=login');
        exit;
    }
}
