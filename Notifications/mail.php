<?php
// config/mail.php

class Mailer
{
    private $apiKey;
    private $fromEmail;
    private $fromName;

    public function __construct()
    {
        $this->apiKey = getenv('BREVO_API_KEY');
        $this->fromEmail = getenv('SMTP_FROM');
        $this->fromName = getenv('SMTP_FROM_NAME');

        if (!$this->apiKey || !$this->fromEmail || !$this->fromName) {
            error_log("Faltan variables de entorno para el correo");
        }
    }

    /**
     * Enviar correo de recuperación de contraseña
     */
    public function enviarRecuperacion($nombre, $email, $token)
    {
        $resetLink = "https://tienda-virtual-production-c3df.up.railway.app/index.php?action=restablecer&token=" . $token;

        $subject = "Recupera tu acceso - NAYLEX STORE";

        $htmlContent = $this->getTemplateRecuperacion($nombre, $resetLink);

        return $this->sendEmail($email, $subject, $htmlContent);
    }

    /**
     * Enviar correo genérico
     */
    private function sendEmail($to, $subject, $htmlContent)
    {
        $url = 'https://api.brevo.com/v3/smtp/email';

        $data = [
            'sender' => [
                'name' => $this->fromName,
                'email' => $this->fromEmail
            ],
            'to' => [
                [
                    'email' => $to,
                    'name' => explode('@', $to)[0]
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $this->apiKey,
            'content-type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("✅ Correo enviado a: " . $to);
            return true;
        } else {
            error_log("❌ Error al enviar correo: " . $response);
            return false;
        }
    }

    /**
     * Template HTML para correo de recuperación de contraseña
     */
    private function getTemplateRecuperacion($nombre, $resetLink)
    {
        $year = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f7fa; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
        
        <!-- Cabecera con gradiente navy/dorado -->
        <div style="background: linear-gradient(135deg, #0a1628 0%, #1a2744 100%); padding: 32px 24px; text-align: center;">
            <h1 style="color: #d4a853; font-size: 24px; margin: 0; font-weight: 700; letter-spacing: 0.5px;">
                NAVI FLEX
            </h1>
            <p style="color: #ffffff; margin-top: 8px; font-size: 14px; opacity: 0.9;">
                Recuperación de Contraseña
            </p>
        </div>
        
        <!-- Contenido -->
        <div style="padding: 32px 24px; color: #2d3748;">
            <p style="font-size: 16px; line-height: 1.6; margin-bottom: 24px;">
                Hola <strong style="color: #0a1628;">{$nombre}</strong>,
            </p>
            
            <p style="font-size: 16px; line-height: 1.6; margin-bottom: 24px; color: #4a5568;">
                Recibimos una solicitud para restablecer la contraseña de tu cuenta en 
                <strong style="color: #0a1628;">NAYLEX STORE</strong>.
            </p>
            
            <div style="background-color: #f8f9fc; border-left: 4px solid #d4a853; padding: 24px; margin-bottom: 24px; border-radius: 0 8px 8px 0;">
                <p style="font-size: 16px; margin: 0 0 12px 0; font-weight: 600; color: #0a1628;">
                    Creá una nueva contraseña
                </p>
                <p style="font-size: 14px; color: #718096; margin: 0 0 20px 0;">
                    Hacé clic en el botón de abajo para restablecer tu contraseña:
                </p>
                <div style="text-align: center; margin: 24px 0;">
                    <a href="{$resetLink}" 
                       style="background: linear-gradient(135deg, #0a1628 0%, #1a2744 100%); 
                              color: #ffffff; padding: 14px 40px; 
                              text-decoration: none; border-radius: 8px; font-weight: 600; 
                              display: inline-block; font-size: 16px;
                              border: 1px solid #d4a853;">
                        Restablecer contraseña
                    </a>
                </div>
                <p style="font-size: 13px; color: #a0aec0; margin: 16px 0 0 0;">
                    Si el botón no funciona, copiá y pegá este enlace en tu navegador:
                </p>
                <p style="font-size: 13px; color: #2d3748; word-break: break-all; background: #ffffff; padding: 12px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    {$resetLink}
                </p>
            </div>
            
            <div style="background-color: #fffbeb; border: 1px solid #fbd38d; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                <p style="font-size: 13px; margin: 0; color: #744210;">
                    ⚠️ Este enlace es válido por <strong>24 horas</strong>. Si no solicitaste este cambio, podés ignorar este correo y tu contraseña no se modificará.
                </p>
            </div>
            
            <div style="text-align: center; margin: 24px 0;">
                <a href="https://tienda-virtual-production-c3df.up.railway.app/index.php?action=login" 
                   style="color: #d4a853; text-decoration: none; font-size: 14px; font-weight: 600;">
                    ← Volver al Login
                </a>
            </div>
            
            <p style="font-size: 13px; color: #a0aec0; line-height: 1.6; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
                ¿Necesitás ayuda? Contactanos:<br>
                📧 soporte@naviflexstore.com<br>
                📞 (+57) 313 333 62 27
            </p>
        </div>
        
        <!-- Pie de página -->
        <div style="background-color: #0a1628; padding: 20px; text-align: center;">
            <p style="color: #d4a853; font-size: 14px; font-weight: 700; margin: 0 0 4px 0;">
                NAVI FLEX
            </p>
            <p style="color: #a0aec0; font-size: 12px; margin: 0;">
                © {$year} NAYLEX STORE — Todos los derechos reservados<br>
                <span style="font-size: 11px;">Desarrollado por TechSolutions</span>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}
