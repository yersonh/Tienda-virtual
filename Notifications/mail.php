<?php

class Mailer
{
    private $apiKey;
    private $fromEmail;
    private $fromName;

    private const BASE_URL = 'https://tienda-virtual-production-c3df.up.railway.app';

    public function __construct()
    {
        $this->apiKey    = getenv('BREVO_API_KEY');
        $this->fromEmail = getenv('SMTP_FROM');
        $this->fromName  = getenv('SMTP_FROM_NAME');

        if (!$this->apiKey || !$this->fromEmail || !$this->fromName) {
            error_log("Faltan variables de entorno para el correo");
        }
    }

    public function enviarRecuperacion($nombre, $email, $token)
    {
        $link = self::BASE_URL . '/index.php?action=restablecer&token=' . $token;

        $body = '
            <p style="font-size:16px;line-height:1.6;margin-bottom:24px;color:#4a5568;">
                Recibimos una solicitud para restablecer la contraseña de tu cuenta en
                <strong style="color:#0a1628;">NAYLEX STORE</strong>.
            </p>';

        $card = [
            'title'   => 'Creá una nueva contraseña',
            'sub'     => 'Hacé clic en el botón de abajo para restablecer tu contraseña:',
            'btn'     => 'Restablecer contraseña',
            'warning' => 'Si no solicitaste este cambio, podés ignorar este correo y tu contraseña no se modificará.',
        ];

        return $this->sendEmail(
            $email,
            'Recupera tu acceso - NAYLEX STORE',
            $this->buildEmailTemplate('Recuperación de Contraseña', $nombre, $body, $card, $link)
        );
    }

    public function enviarReactivacion($nombre, $email, $token)
    {
        error_log('[MAILER] TOKEN_RECIBIDO=' . $token);
        $link = self::BASE_URL . '/index.php?action=confirmarReactivacion&token=' . urlencode($token);

        $body = '
            <p style="font-size:16px;line-height:1.6;margin-bottom:24px;color:#4a5568;">
                Recibimos una solicitud para reactivar tu cuenta en
                <strong style="color:#0a1628;">NAYLEX STORE</strong>.
                Tus pedidos y datos se han conservado.
            </p>';

        $card = [
            'title'   => 'Reactivar mi cuenta',
            'sub'     => 'Hacé clic en el botón de abajo para reactivar tu cuenta:',
            'btn'     => 'Reactivar cuenta',
            'warning' => 'Si no solicitaste esta reactivación, podés ignorar este correo.',
        ];

        return $this->sendEmail(
            $email,
            'Reactiva tu cuenta - NAYLEX STORE',
            $this->buildEmailTemplate('Reactivación de Cuenta', $nombre, $body, $card, $link)
        );
    }

    private function sendEmail($to, $subject, $htmlContent)
    {
        $data = [
            'sender'      => ['name' => $this->fromName, 'email' => $this->fromEmail],
            'to'          => [['email' => $to, 'name' => explode('@', $to)[0]]],
            'subject'     => $subject,
            'htmlContent' => $htmlContent,
        ];

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER    => [
                'accept: application/json',
                'api-key: ' . $this->apiKey,
                'content-type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Correo enviado a: {$to}");
            return true;
        }

        error_log("Error al enviar correo: {$response}");
        return false;
    }

    private function buildEmailTemplate(
        string $heading,
        string $nombre,
        string $bodyHtml,
        array  $card,
        string $link
    ): string {
        $year    = date('Y');
        $loginUrl = self::BASE_URL . '/index.php?action=login';
        $cardTitle   = htmlspecialchars($card['title'],   ENT_QUOTES, 'UTF-8');
        $cardSub     = htmlspecialchars($card['sub'],     ENT_QUOTES, 'UTF-8');
        $cardBtn     = htmlspecialchars($card['btn'],     ENT_QUOTES, 'UTF-8');
        $cardWarning = htmlspecialchars($card['warning'], ENT_QUOTES, 'UTF-8');
        $safeNombre  = htmlspecialchars($nombre,          ENT_QUOTES, 'UTF-8');
        $safeLink    = htmlspecialchars($link,            ENT_QUOTES, 'UTF-8');
        $safeHeading = htmlspecialchars($heading,         ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family:'Segoe UI',Arial,sans-serif;background-color:#f5f7fa;margin:0;padding:0;">
<div style="max-width:600px;margin:0 auto;background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

    <div style="background:linear-gradient(135deg,#0a1628 0%,#1a2744 100%);padding:32px 24px;text-align:center;">
        <h1 style="color:#d4a853;font-size:24px;margin:0;font-weight:700;letter-spacing:0.5px;">NAYLEX STORE</h1>
        <p style="color:#ffffff;margin-top:8px;font-size:14px;opacity:0.9;">{$safeHeading}</p>
    </div>

    <div style="padding:32px 24px;color:#2d3748;">
        <p style="font-size:16px;line-height:1.6;margin-bottom:24px;">
            Hola <strong style="color:#0a1628;">{$safeNombre}</strong>,
        </p>

        {$bodyHtml}

        <div style="background-color:#f8f9fc;border-left:4px solid #d4a853;padding:24px;margin-bottom:24px;border-radius:0 8px 8px 0;">
            <p style="font-size:16px;margin:0 0 12px 0;font-weight:600;color:#0a1628;">{$cardTitle}</p>
            <p style="font-size:14px;color:#718096;margin:0 0 20px 0;">{$cardSub}</p>
            <div style="text-align:center;margin:24px 0;">
                <a href="{$safeLink}"
                   style="background:linear-gradient(135deg,#0a1628 0%,#1a2744 100%);color:#ffffff;padding:14px 40px;text-decoration:none;border-radius:8px;font-weight:600;display:inline-block;font-size:16px;border:1px solid #d4a853;">
                    {$cardBtn}
                </a>
            </div>
            <p style="font-size:13px;color:#a0aec0;margin:16px 0 0 0;">Si el botón no funciona, copiá y pegá este enlace en tu navegador:</p>
            <p style="font-size:13px;color:#2d3748;word-break:break-all;background:#ffffff;padding:12px;border-radius:6px;border:1px solid #e2e8f0;">{$safeLink}</p>
        </div>

        <div style="background-color:#fffbeb;border:1px solid #fbd38d;padding:16px;border-radius:8px;margin-bottom:24px;">
            <p style="font-size:13px;margin:0;color:#744210;">
                ⚠️ Este enlace es válido por <strong>24 horas</strong>. {$cardWarning}
            </p>
        </div>

        <div style="text-align:center;margin:24px 0;">
            <a href="{$loginUrl}" style="color:#d4a853;text-decoration:none;font-size:14px;font-weight:600;">← Volver al Login</a>
        </div>

        <p style="font-size:13px;color:#a0aec0;line-height:1.6;margin-top:24px;padding-top:24px;border-top:1px solid #e2e8f0;">
            ¿Necesitás ayuda? Contactanos:<br>
            📧 soporte@naviflexstore.com<br>
            📞 (+57) 313 333 62 27
        </p>
    </div>

    <div style="background-color:#0a1628;padding:20px;text-align:center;">
        <p style="color:#d4a853;font-size:14px;font-weight:700;margin:0 0 4px 0;">NAVI FLEX</p>
        <p style="color:#a0aec0;font-size:12px;margin:0;">
            &copy; {$year} NAYLEX STORE &mdash; Todos los derechos reservados<br>
            <span style="font-size:11px;">Desarrollado por TechSolutions</span>
        </p>
    </div>

</div>
</body>
</html>
HTML;
    }
}
