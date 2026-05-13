<?php

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../Notifications/mail.php';

class RecuperarController
{
    public function solicitarRecuperacion(): void
    {
        requirePost('recuperar');

        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

        if ($email === '') {
            redirectTo('recuperar', 'Por favor, ingresá tu correo electrónico.');
        }

        $usuarioModel = new UsuarioModel();
        $usuario      = $usuarioModel->buscarPorEmail($email);

        if ($usuario) {
            $token  = $usuarioModel->generarTokenRecuperacion($usuario['ID_USUARIO']);
            $mailer = new Mailer();
            $enviado = $mailer->enviarRecuperacion($usuario['NOMBRES'], $usuario['CORREO'], $token);

            if (!$enviado) {
                redirectTo('recuperar', 'Error al enviar el correo. Por favor, intentá de nuevo.');
            }
        }

        redirectTo(
            'recuperar',
            'Si el correo existe en nuestro sistema, recibirás las instrucciones.',
            'success'
        );
    }

    public function mostrarRestablecer(): void
    {
        $token      = $_GET['token'] ?? '';
        $tokenValido = false;

        if ($token !== '') {
            $usuarioModel = new UsuarioModel();
            $tokenValido  = (bool) $usuarioModel->validarToken($token);
        }

        require_once __DIR__ . '/../views/Restablecer.php';
    }

    public function cambiarPassword(): void
    {
        requirePost('recuperar');

        $token    = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if ($token === '' || $password === '' || $confirmar === '') {
            redirectTo('restablecer&token=' . urlencode($token), 'Todos los campos son obligatorios.');
        }

        if (strlen($password) < 8) {
            redirectTo('restablecer&token=' . urlencode($token), 'La contraseña debe tener al menos 8 caracteres.');
        }

        if ($password !== $confirmar) {
            redirectTo('restablecer&token=' . urlencode($token), 'Las contraseñas no coinciden.');
        }

        $usuarioModel = new UsuarioModel();
        $datos        = $usuarioModel->validarToken($token);

        if (!$datos) {
            redirectTo('recuperar', 'El enlace es inválido o expiró. Solicitá uno nuevo.');
        }

        $usuarioModel->actualizarPassword($datos['USUARIO_ID'], $password);
        $usuarioModel->marcarTokenUsado($token);

        redirectTo('login', 'Contraseña actualizada correctamente. Ya podés iniciar sesión.', 'success');
    }
}
