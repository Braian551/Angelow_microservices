<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

class WelcomeEmailService
{
    public function send(string $email, string $name): bool
    {
        $normalizedEmail = trim($email);
        if ($normalizedEmail === '' || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) config('services.phpmailer.host', 'smtp.gmail.com');
            $mail->Port = (int) config('services.phpmailer.port', 587);
            $mail->CharSet = 'UTF-8';

            $username = trim((string) config('services.phpmailer.username', ''));
            $password = (string) config('services.phpmailer.password', '');
            $encryption = strtolower(trim((string) config('services.phpmailer.encryption', 'tls')));

            $mail->SMTPAuth = ($username !== '' && $password !== '');
            if ($mail->SMTPAuth) {
                $mail->Username = $username;
                $mail->Password = $password;
            }

            $mail->SMTPSecure = $encryption === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $fromEmail = trim((string) config('services.phpmailer.from_email', 'seguridad@angelow.com'));
            $fromName = trim((string) config('services.phpmailer.from_name', 'Angelow'));
            $mail->setFrom($fromEmail, $fromName);

            $recipientName = trim($name) !== '' ? trim($name) : 'Cliente Angelow';
            $mail->addAddress($normalizedEmail, $recipientName);

            $logoPath = public_path('images/logo2.png');
            $logoEmbedId = 'welcome_logo';
            $logoUrl = $this->buildLogoUrl();

            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, $logoEmbedId);
                $logoUrl = 'cid:' . $logoEmbedId;
            }

            $mail->isHTML(true);
            $mail->Subject = '¡Bienvenido/a a Angelow!';
            $mail->Body = $this->buildWelcomeTemplate($recipientName, $logoUrl, $this->buildStoreUrl());

            $mail->send();
            return true;
        } catch (MailException $exception) {
            Log::warning('No se pudo enviar correo de bienvenida.', [
                'email' => $normalizedEmail,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function buildWelcomeTemplate(string $name, string $logoUrl, string $storeUrl): string
    {
        $safeName = e($name);
        $safeLogoUrl = e($logoUrl);
        $safeStoreUrl = e($storeUrl);

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a Angelow</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background: #f5f7fb; margin: 0; padding: 24px 0; }
        .wrapper { max-width: 620px; margin: 0 auto; }
        .card { background: #ffffff; border: 1px solid #dbe5f0; border-radius: 14px; overflow: hidden; }
        .header { background: #0f7abf; color: #ffffff; text-align: center; padding: 26px 24px; }
        .header img { max-height: 50px; margin-bottom: 12px; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 24px; }
        .content p { margin: 0 0 14px; }
        .benefits { margin: 0 0 18px; padding-left: 20px; }
        .benefits li { margin-bottom: 6px; }
        .cta { margin-top: 8px; }
        .cta a { display: inline-block; background: #0f7abf; color: #ffffff; text-decoration: none; padding: 11px 16px; border-radius: 9px; font-weight: 700; }
        .footer { border-top: 1px solid #e5edf6; padding: 14px 24px; color: #64748b; font-size: 12px; background: #f8fbff; text-align: center; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <img src="' . $safeLogoUrl . '" alt="Angelow">
                <h1>¡Bienvenido/a a Angelow!</h1>
            </div>
            <div class="content">
                <p>Hola <strong>' . $safeName . '</strong>,</p>
                <p>Gracias por crear tu cuenta en Angelow. Ya puedes disfrutar de todos los beneficios de nuestra tienda:</p>
                <ul class="benefits">
                    <li>Acceso a promociones exclusivas.</li>
                    <li>Seguimiento en tiempo real de tus pedidos.</li>
                    <li>Historial de compras y estado de pago.</li>
                    <li>Gestión rápida de direcciones y preferencias.</li>
                </ul>
                <p class="cta">
                    <a href="' . $safeStoreUrl . '">Ir a Angelow</a>
                </p>
            </div>
            <div class="footer">
                © ' . date('Y') . ' Angelow. Si no realizaste este registro, ignora este mensaje.
            </div>
        </div>
    </div>
</body>
</html>';
    }

    private function buildStoreUrl(): string
    {
        $url = trim((string) config('services.password_recovery.frontend_url', 'http://localhost:5173'));
        if ($url === '') {
            return 'http://localhost:5173';
        }

        return rtrim($url, '/');
    }

    private function buildLogoUrl(): string
    {
        return $this->buildStoreUrl() . '/logo_principal.png';
    }
}
