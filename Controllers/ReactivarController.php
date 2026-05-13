<?php

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../Notifications/mail.php';

class ReactivarController
{
    private function generarToken(int $userId): string
    {
        $expiry = time() + 86400;
        $secret = getenv('BREVO_API_KEY') ?: 'nxl-reactivacion-secret';
        $hmac   = substr(hash_hmac('sha256', $userId . '.' . $expiry, $secret), 0, 40);
        return rtrim(strtr(base64_encode($userId . '.' . $expiry . '.' . $hmac), '+/', '-_'), '=');
    }

    private function verificarToken(string $token): ?int
    {
        $padded  = strtr($token, '-_', '+/');
        $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
        $decoded  = base64_decode($padded, true);
        if ($decoded === false) return null;

        $parts = explode('.', $decoded, 3);
        if (count($parts) !== 3) return null;

        [$userId, $expiry, $hmac] = $parts;

        if (!ctype_digit($userId) || !ctype_digit($expiry)) return null;
        if (time() > (int) $expiry) return null;

        $secret   = getenv('BREVO_API_KEY') ?: 'nxl-reactivacion-secret';
        $expected = substr(hash_hmac('sha256', $userId . '.' . $expiry, $secret), 0, 40);

        if (!hash_equals($expected, $hmac)) return null;

        return (int) $userId;
    }

    public function solicitarReactivacion(): void
    {
        requirePost('reactivar');

        $identificador = trim($_POST['identificador'] ?? '');

        if ($identificador === '') {
            redirectTo('reactivar', 'Por favor, ingresá tu usuario, correo o teléfono.');
        }

        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->buscarPorIdentificador($identificador);

        if ($usuario && $usuario['ESTADO'] !== 'ACTIVO') {
            try {
                $token  = $this->generarToken((int) $usuario['ID_USUARIO']);
                error_log('[REACTIVACION] TOKEN_GENERADO=' . $token);
                $mailer = new Mailer();
                $mailer->enviarReactivacion($usuario['NOMBRES'], $usuario['CORREO'], $token);
            } catch (\Throwable $e) {
                error_log('Error enviando correo de reactivación: ' . $e->getMessage());
            }
        }

        redirectTo(
            'reactivar',
            'Si los datos corresponden a una cuenta inactiva, recibirás un correo con el enlace.',
            'success'
        );
    }

    public function confirmarReactivacion(): void
    {
        $token = trim($_GET['token'] ?? '');
        error_log('[REACTIVACION] TOKEN_RECIBIDO_CONFIRMACION=' . $token);

        $idUsuario = $token !== '' ? $this->verificarToken($token) : null;

        if (!$idUsuario) {
            redirectTo('reactivar', 'El enlace de reactivación es inválido o ya expiró. Solicitá uno nuevo.');
        }

        $usuarioModel = new UsuarioModel();

        try {
            $usuarioModel->reactivarUsuario($idUsuario);
            redirectTo('login', 'Tu cuenta ha sido reactivada. Ya podés iniciar sesión.', 'success');
        } catch (\Throwable $e) {
            error_log('Error reactivando cuenta: ' . $e->getMessage());
            redirectTo('reactivar', 'No se pudo reactivar la cuenta. Intentá de nuevo.');
        }
    }
}
