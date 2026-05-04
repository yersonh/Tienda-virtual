<?php

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../Notifications/mail.php';

class RecuperarController
{

    /**
     * Procesar solicitud de recuperación (envío de correo)
     */
    public function solicitarRecuperacion()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=recuperar');
            exit;
        }

        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);

        if (empty($email)) {
            $_SESSION['error'] = 'Por favor, ingresá tu correo electrónico.';
            header('Location: index.php?action=recuperar');
            exit;
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->buscarPorEmail($email);

        if ($usuario) {
            $token = $usuarioModel->generarTokenRecuperacion($usuario['ID_USUARIO']);

            $mailer = new Mailer();
            $enviado = $mailer->enviarRecuperacion(
                $usuario['NOMBRES'],
                $usuario['CORREO'],
                $token
            );

            if ($enviado) {
                $_SESSION['success'] = 'Te enviamos un correo con las instrucciones para restablecer tu contraseña.';
            } else {
                $_SESSION['error'] = 'Error al enviar el correo. Por favor, intentá de nuevo.';
            }
        } else {
            $_SESSION['success'] = 'Si el correo existe en nuestro sistema, recibirás las instrucciones.';
        }

        header('Location: index.php?action=recuperar');
        exit;
    }

    /**
     * Mostrar formulario para restablecer contraseña
     */
    public function mostrarRestablecer()
    {
        $token = $_GET['token'] ?? '';
        $tokenValido = false;

        if (!empty($token)) {
            $usuarioModel = new UsuarioModel();
            $datos = $usuarioModel->validarToken($token);
            if ($datos) {
                $tokenValido = true;
            }
        }

        require_once __DIR__ . '/../views/Restablecer.php';
    }

    /**
     * Procesar cambio de contraseña
     */
    public function cambiarPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=recuperar');
            exit;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmar = $_POST['confirmar'] ?? '';

        if (empty($token) || empty($password) || empty($confirmar)) {
            $_SESSION['error'] = 'Todos los campos son obligatorios.';
            header('Location: index.php?action=restablecer&token=' . urlencode($token));
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'La contraseña debe tener al menos 8 caracteres.';
            header('Location: index.php?action=restablecer&token=' . urlencode($token));
            exit;
        }

        if ($password !== $confirmar) {
            $_SESSION['error'] = 'Las contraseñas no coinciden.';
            header('Location: index.php?action=restablecer&token=' . urlencode($token));
            exit;
        }

        $usuarioModel = new UsuarioModel();
        $datos = $usuarioModel->validarToken($token);

        if (!$datos) {
            $_SESSION['error'] = 'El enlace es inválido o expiró. Solicitá uno nuevo.';
            header('Location: index.php?action=recuperar');
            exit;
        }

        $usuarioModel->actualizarPassword($datos['USUARIO_ID'], $password);
        $usuarioModel->marcarTokenUsado($token);

        $_SESSION['success'] = 'Contraseña actualizada correctamente. Ya podés iniciar sesión.';
        header('Location: index.php?action=login');
        exit;
    }
}
