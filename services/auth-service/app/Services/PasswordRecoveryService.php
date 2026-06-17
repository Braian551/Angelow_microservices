<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\PasswordReset;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Servicio del flujo completo de recuperación de contraseña.
 *
 * Implementa 4 pasos: solicitar código, reenviar, verificar y
 * restablecer contraseña. Usa PHPMailer para envío de correos
 * con plantilla visual heredada del legacy Angelow. Incluye
 * cooldown anti-spam en Redis/Cache y enmascaramiento seguro
 * del identificador en las respuestas.
 */
class PasswordRecoveryService
{
    /**
     * Solicita o reenvía un código de recuperación al correo/teléfono.
     *
     * Normaliza el identificador, busca al usuario, verifica cooldown,
     * genera código de 4 dígitos, lo almacena hasheado en password_resets,
     * envía el correo y activa la ventana anti-spam.
     *
     * @param  string  $identifier  correo o teléfono
     * @param  bool    $isResend    true si es reenvío (cambia mensaje de respuesta)
     * @return array{message:string,data:array<string,mixed>}
     * @throws AuthException si el identificador es inválido, no hay cuenta, cooldown activo o falla el envío
     */
    public function requestCode(string $identifier, bool $isResend = false): array
    {
        // Normaliza el identificador (email en lowercase, teléfono solo dígitos)
        $normalizedIdentifier = $this->normalizeIdentifier($identifier);
        if ($normalizedIdentifier === '') {
            throw new AuthException(
                'Debes ingresar el correo electrónico o teléfono asociado a tu cuenta.',
                422
            );
        }

        // Busca al usuario por email o teléfono
        $user = $this->findUserByIdentifier($normalizedIdentifier);
        if (!$user) {
            throw new AuthException(
                'No encontramos una cuenta asociada a esos datos. Verifica la información e inténtalo de nuevo.',
                404
            );
        }

        // Verifica cooldown anti-spam
        $cooldown = $this->secondsUntilNextCode($user->id);
        if ($cooldown > 0) {
            throw new AuthException(
                "Ya enviamos un código recientemente. Intenta de nuevo en {$cooldown} segundos.",
                429
            );
        }

        // Genera código aleatorio de 4 dígitos
        $codeLength = $this->getCodeLength();
        $maxValue = (10 ** $codeLength) - 1;
        $code = str_pad((string) random_int(0, $maxValue), $codeLength, '0', STR_PAD_LEFT);
        $expiresAt = now()->addSeconds($this->getCodeTtlSeconds());

        // Persiste el código hasheado en la tabla password_resets
        PasswordReset::query()->create([
            'user_id' => $user->id,
            'token' => Hash::make($code),
            'expires_at' => $expiresAt,
            'is_used' => false,
        ]);

        // Envía el código por correo; si falla, no se crea el registro
        if (!$this->sendRecoveryEmail($user, $code, $expiresAt)) {
            throw new AuthException(
                'No pudimos enviar el correo de verificación en este momento. Inténtalo nuevamente.',
                500
            );
        }

        // Activa cooldown para evitar reenvío inmediato
        $this->startResendCooldown($user->id);

        return [
            'message' => $isResend
                ? 'Generamos un nuevo código y lo enviamos a tu correo.'
                : 'Te enviamos un código de verificación a tu correo.',
            'data' => [
                'expires_at' => $expiresAt->toDateTimeString(),
                'expires_in' => $this->getCodeTtlSeconds(),
                'identifier' => $this->maskIdentifier($normalizedIdentifier),
                'resend_cooldown' => $this->getResendCooldownSeconds(),
            ],
        ];
    }

    /**
     * Verifica el código ingresado y emite un session_token temporal.
     *
     * El session_token se guarda en caché (Redis) con la misma
     * duración que el código y permite al usuario cambiar la
     * contraseña sin necesidad de autenticación adicional.
     *
     * @return array{session_token:string}
     * @throws AuthException si el código expiró, ya fue usado o es inválido
     */
    public function verifyCode(string $identifier, string $code): array
    {
        $normalizedIdentifier = $this->normalizeIdentifier($identifier);
        if ($normalizedIdentifier === '') {
            throw new AuthException(
                'Debes ingresar el correo electrónico o teléfono asociado a tu cuenta.',
                422
            );
        }

        if (!preg_match('/^[0-9]{4}$/', trim($code))) {
            throw new AuthException(
                'El código debe tener 4 dígitos.',
                422
            );
        }

        $user = $this->findUserByIdentifier($normalizedIdentifier);
        if (!$user) {
            throw new AuthException(
                'No encontramos solicitudes activas para esos datos.',
                404
            );
        }

        // Obtiene el último registro de código para este usuario
        $record = PasswordReset::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (!$record) {
            throw new AuthException(
                'No encontramos solicitudes activas para esos datos.',
                404
            );
        }

        if ($record->is_used) {
            throw new AuthException(
                'Ese código ya fue utilizado. Solicita uno nuevo.',
                409
            );
        }

        if ($record->expires_at->isPast()) {
            throw new AuthException(
                'El código expiró. Solicita uno nuevo para continuar.',
                410
            );
        }

        if (!Hash::check($code, $record->token)) {
            throw new AuthException(
                'El código ingresado no es válido.',
                422
            );
        }

        // Genera token de sesión temporal para el cambio de contraseña
        $sessionToken = Str::random(64);
        Cache::put(
            $this->sessionCacheKey($sessionToken),
            [
                'user_id' => (string) $user->id,
                'reset_id' => (int) $record->id,
            ],
            now()->addSeconds($this->getCodeTtlSeconds())
        );

        return [
            'session_token' => $sessionToken,
        ];
    }

    /**
     * Actualiza la contraseña usando el session_token emitido tras validar el código.
     *
     * Valida que el session_token sea válido y no haya expirado,
     * verifica que el registro de password_reset asociado siga
     * vigente, y ejecuta la actualización en una transacción.
     *
     * @throws AuthException si el token es inválido, la contraseña no cumple requisitos
     */
    public function resetPassword(string $sessionToken, string $password, string $passwordConfirmation): void
    {
        $sessionToken = trim($sessionToken);
        if ($sessionToken === '') {
            throw new AuthException(
                'La sesión de recuperación no es válida o expiró. Vuelve a solicitar un código.',
                422
            );
        }

        if ($password !== $passwordConfirmation) {
            throw new AuthException(
                'Las contraseñas ingresadas no coinciden.',
                422
            );
        }

        $passwordLength = mb_strlen($password);
        if ($passwordLength < 8) {
            throw new AuthException(
                'La nueva contraseña debe tener al menos 8 caracteres.',
                422
            );
        }

        if ($passwordLength > 64) {
            throw new AuthException(
                'La nueva contraseña no puede exceder 64 caracteres.',
                422
            );
        }

        $cacheKey = $this->sessionCacheKey($sessionToken);
        $context = Cache::get($cacheKey);

        if (!is_array($context)) {
            throw new AuthException(
                'La sesión de recuperación no es válida o expiró. Vuelve a solicitar un código.',
                410
            );
        }

        $userId = (string) ($context['user_id'] ?? '');
        $resetId = (int) ($context['reset_id'] ?? 0);

        // Verifica que el registro de password_reset siga siendo válido
        $record = PasswordReset::query()
            ->where('id', $resetId)
            ->where('user_id', $userId)
            ->first();

        if (!$record || $record->is_used || $record->expires_at->isPast()) {
            Cache::forget($cacheKey);
            throw new AuthException(
                'La sesión de recuperación no es válida o expiró. Vuelve a solicitar un código.',
                410
            );
        }

        // Transacción: actualiza contraseña y marca código como usado
        DB::transaction(function () use ($userId, $record, $password): void {
            $updatedUsers = User::query()
                ->where('id', $userId)
                ->update([
                    'password' => Hash::make($password),
                    'remember_token' => null,
                    'token_expiry' => null,
                    'updated_at' => now(),
                ]);

            if ($updatedUsers === 0) {
                throw new AuthException(
                    'No encontramos una cuenta válida para completar la recuperación.',
                    404
                );
            }

            $record->is_used = true;
            $record->save();
        });

        // Limpia la caché del session_token (uso único)
        Cache::forget($cacheKey);
    }

    /**
     * Busca un usuario por correo electrónico o teléfono.
     */
    private function findUserByIdentifier(string $identifier): ?User
    {
        return User::query()
            ->where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();
    }

    /**
     * Envía el correo de recuperación usando PHPMailer con plantilla visual del legacy.
     *
     * Configura SMTP, incrusta el logo del sitio como imagen embebida,
     * y construye el cuerpo HTML con la plantilla de recuperación.
     */
    private function sendRecoveryEmail(User $user, string $code, Carbon $expiresAt): bool
    {
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
            $fromName = trim((string) config('services.phpmailer.from_name', 'Seguridad Angelow'));
            $mail->setFrom($fromEmail, $fromName);

            $recipientName = trim((string) $user->name) !== '' ? (string) $user->name : 'Cliente Angelow';
            $mail->addAddress((string) $user->email, $recipientName);

            // Incrusta el logo como imagen embebida (CID)
            $logoPath = public_path('images/logo2.png');
            $logoEmbedId = 'recovery_logo';
            $logoUrl = $this->buildLogoUrl();

            if (is_file($logoPath)) {
                $mail->addEmbeddedImage($logoPath, $logoEmbedId);
                $logoUrl = 'cid:' . $logoEmbedId;
            }

            $recoveryUrl = $this->buildRecoveryUrl();

            $mail->isHTML(true);
            $mail->Subject = 'Tu código para restablecer la contraseña';
            $mail->Body = $this->buildRecoveryEmailTemplate(
                $recipientName,
                $code,
                $expiresAt->format('d/m/Y H:i'),
                $logoUrl,
                $recoveryUrl
            );

            $mail->send();
            return true;
        } catch (MailException $exception) {
            Log::error('Error al enviar correo de recuperación', [
                'message' => $exception->getMessage(),
                'user_id' => $user->id,
            ]);
            return false;
        }
    }

    /**
     * Construye la plantilla HTML del correo de recuperación.
     *
     * Reutiliza la plantilla visual del legacy Angelow con los estilos,
     * colores y disposición originales. Escapa todas las variables
     * con e() para prevenir XSS en el correo.
     */
    private function buildRecoveryEmailTemplate(
        string $name,
        string $code,
        string $formattedExpiry,
        string $logoUrl,
        string $recoveryUrl
    ): string {
        $safeName = e($name);
        $safeExpiry = e($formattedExpiry);
        $safeLogoUrl = e($logoUrl);
        $safeRecoveryUrl = e($recoveryUrl);
        $safeCode = trim(chunk_split($code, 1, ' '));

        return '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código de verificación Angelow</title>
    <style>
        body { font-family: "Inter", Arial, sans-serif; margin:0; padding:0; background:#f6f7fb; color:#0f172a; }
        .wrapper { max-width:600px; margin:0 auto; padding:24px; }
        .card { background:#ffffff; border-radius:18px; box-shadow:0 20px 45px rgba(15,23,42,0.12); overflow:hidden; border:1px solid #eef2ff; }
        .header { background:linear-gradient(135deg,#0f4c81,#2968c8); padding:36px 32px; text-align:center; color:#fff; }
        .header img { max-height:54px; margin-bottom:16px; }
        .header h1 { margin:0; font-size:24px; }
        .body { padding:32px; }
        .code-box { display:flex; justify-content:center; letter-spacing:12px; font-size:32px; font-weight:700; color:#0f172a; margin:24px 0; }
        .info { font-size:14px; color:#475569; line-height:1.6; }
        .pill { display:inline-block; padding:6px 14px; border-radius:999px; background:#e0f2ff; color:#0369a1; font-weight:600; font-size:12px; margin-top:16px; }
        .footer { padding:24px 32px; background:#f8fafc; font-size:12px; color:#94a3b8; text-align:center; }
        .cta { text-align:center; margin-top:24px; }
        .cta a { display:inline-block; padding:12px 20px; border-radius:999px; background:#0b6bbd; color:#fff; text-decoration:none; font-weight:600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="header">
                <img src="' . $safeLogoUrl . '" alt="Angelow">
                <h1>Protegemos tu cuenta</h1>
                <p style="opacity:0.85; font-size:14px; margin-top:8px;">Usa este código para restablecer tu contraseña</p>
            </div>
            <div class="body">
                <p style="font-size:16px; color:#0f172a;">Hola ' . $safeName . ',</p>
                <p class="info">Recibimos una solicitud para restablecer tu contraseña. Copia el siguiente código en la pantalla de verificación para continuar:</p>
                <div class="code-box">' . $safeCode . '</div>
                <p class="info">El código expira el <strong>' . $safeExpiry . '</strong>. Por seguridad, no lo compartas con nadie.</p>
                <div class="pill">Código válido por 15 minutos</div>
                <div class="cta">
                    <a href="' . $safeRecoveryUrl . '">Abrir recuperador</a>
                </div>
                <p class="info" style="margin-top:24px;">Si tú no solicitaste este cambio, ignora este mensaje. Tu contraseña actual seguirá siendo válida.</p>
            </div>
            <div class="footer">
                &copy; ' . date('Y') . ' Angelow &middot; Mensaje generado automáticamente.
            </div>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Normaliza el identificador (correo o teléfono) y descarta formatos inválidos.
     *
     * Si es email, lo convierte a minúsculas. Si es teléfono, extrae solo dígitos
     * y valida que tenga entre 7 y 15 caracteres. Si no cumple, retorna vacío.
     */
    private function normalizeIdentifier(string $value): string
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            return mb_strtolower($trimmed);
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits !== '' && strlen($digits) >= 7 && strlen($digits) <= 15) {
            return $digits;
        }

        return '';
    }

    /**
     * Enmascara el identificador para la respuesta, protegiendo datos sensibles.
     *
     * Para email: muestra solo los primeros 2 caracteres antes del @.
     * Para teléfono: muestra solo los últimos 4 dígitos.
     */
    private function maskIdentifier(string $identifier): string
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            [$name, $domain] = explode('@', $identifier, 2);
            $visible = mb_substr($name, 0, 2);
            $maskedName = $visible . str_repeat('*', max(mb_strlen($name) - 2, 2));
            return $maskedName . '@' . $domain;
        }

        if (preg_match('/^[0-9]+$/', $identifier) === 1) {
            $len = strlen($identifier);
            return str_repeat('*', max($len - 4, 0)) . substr($identifier, -4);
        }

        return $identifier;
    }

    /**
     * Retorna los segundos restantes de cooldown para evitar spam de códigos.
     */
    private function secondsUntilNextCode(string $userId): int
    {
        $cooldownUntil = (int) Cache::get($this->cooldownCacheKey($userId), 0);
        if ($cooldownUntil <= 0) {
            return 0;
        }

        $remaining = $cooldownUntil - time();
        if ($remaining <= 0) {
            Cache::forget($this->cooldownCacheKey($userId));
            return 0;
        }

        return min($remaining, $this->getResendCooldownSeconds());
    }

    /**
     * Activa la ventana anti-spam para reenvío de códigos.
     */
    private function startResendCooldown(string $userId): void
    {
        $cooldownSeconds = $this->getResendCooldownSeconds();
        Cache::put(
            $this->cooldownCacheKey($userId),
            time() + $cooldownSeconds,
            now()->addSeconds($cooldownSeconds)
        );
    }

    /**
     * Clave de caché para el session_token de recuperación.
     */
    private function sessionCacheKey(string $sessionToken): string
    {
        return 'password_recovery:' . $sessionToken;
    }

    /**
     * Clave de caché para el cooldown anti-spam por usuario.
     */
    private function cooldownCacheKey(string $userId): string
    {
        return 'password_recovery:cooldown:' . $userId;
    }

    /**
     * Longitud del código de verificación (4 dígitos).
     */
    private function getCodeLength(): int
    {
        return 4;
    }

    /**
     * Tiempo de vida del código en segundos (configurable vía .env, mínimo 60s).
     */
    private function getCodeTtlSeconds(): int
    {
        return max(60, (int) config('services.password_recovery.code_ttl', 900));
    }

    /**
     * Segundos de espera obligatoria entre reenvíos (60s).
     */
    private function getResendCooldownSeconds(): int
    {
        return 60;
    }

    /**
     * Construye la URL del frontend para el botón "Abrir recuperador".
     */
    private function buildRecoveryUrl(): string
    {
        $frontendUrl = rtrim((string) config('services.password_recovery.frontend_url', ''), '/');
        if ($frontendUrl === '') {
            return '#';
        }

        return $frontendUrl . '/recuperar';
    }

    /**
     * Construye la URL del logo para usar en el correo.
     * Si no hay frontend configurado, usa placeholder.
     */
    private function buildLogoUrl(): string
    {
        $frontendUrl = rtrim((string) config('services.password_recovery.frontend_url', ''), '/');
        if ($frontendUrl === '') {
            return 'https://via.placeholder.com/120x120?text=Angelow';
        }

        return $frontendUrl . '/logo.png';
    }
}
