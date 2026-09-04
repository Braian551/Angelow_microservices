<?php

namespace App\Services;

use App\Exceptions\AuthException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Gestiona códigos temporales para confirmar correos antes de crear cuentas.
 */
class RegistrationVerificationService
{
    private const COURIER_ROLES = ['courier', 'repartidor'];
    /**
     * Genera y envía un código de registro al correo indicado.
     *
     * Reutiliza la configuración SMTP de PHPMailer usada por recuperación,
     * evitando duplicar credenciales o transportes por cada flujo de auth.
     *
     * @return array{message:string,data:array<string,mixed>}
     */
    public function requestCode(string $email, bool $isResend = false): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail === '') {
            throw new AuthException('Ingresa un correo electrónico válido.', 422);
        }

        if (User::query()->where('email', $normalizedEmail)->exists()) {
            throw new AuthException('Este correo ya está registrado.', 409);
        }

        $cooldown = $this->secondsUntilNextCode($normalizedEmail);
        if ($cooldown > 0) {
            throw new AuthException(
                "Ya enviamos un código recientemente. Intenta de nuevo en {$cooldown} segundos.",
                429
            );
        }

        // El código se guarda hasheado en caché para no persistir datos sensibles.
        $code = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $expiresAt = now()->addSeconds($this->getCodeTtlSeconds());
        Cache::put($this->codeCacheKey($normalizedEmail), [
            'hash' => Hash::make($code),
            'expires_at' => $expiresAt->toISOString(),
        ], $expiresAt);

        if (!$this->sendRegistrationEmail($normalizedEmail, $code, $expiresAt)) {
            Cache::forget($this->codeCacheKey($normalizedEmail));
            throw new AuthException('No pudimos enviar el código de verificación. Inténtalo nuevamente.', 500);
        }

        $this->startResendCooldown($normalizedEmail);

        return [
            'message' => $isResend
                ? 'Generamos un nuevo código y lo enviamos a tu correo.'
                : 'Te enviamos un código de verificación a tu correo.',
            'data' => [
                'expires_at' => $expiresAt->toDateTimeString(),
                'expires_in' => $this->getCodeTtlSeconds(),
                'identifier' => $this->maskEmail($normalizedEmail),
                'resend_cooldown' => $this->getResendCooldownSeconds(),
            ],
        ];
    }

    /**
     * Verifica el código de correo y emite un token temporal para completar registro.
     *
     * @return array{registration_token:string}
     */
    public function verifyCode(string $email, string $code): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail === '') {
            throw new AuthException('Ingresa un correo electrónico válido.', 422);
        }

        $record = Cache::get($this->codeCacheKey($normalizedEmail));
        if (!is_array($record)) {
            throw new AuthException('El código expiró. Solicita uno nuevo para continuar.', 410);
        }

        if (!Hash::check(trim($code), (string) ($record['hash'] ?? ''))) {
            throw new AuthException('El código ingresado no es válido.', 422);
        }

        $registrationToken = Str::random(64);
        Cache::put($this->tokenCacheKey($registrationToken), [
            'email' => $normalizedEmail,
        ], now()->addSeconds($this->getCodeTtlSeconds()));
        Cache::forget($this->codeCacheKey($normalizedEmail));

        return ['registration_token' => $registrationToken];
    }

    /**
     * Consume el token emitido para garantizar que el correo final fue verificado.
     */
    public function consumeVerifiedEmail(string $email, string $registrationToken): void
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $token = trim($registrationToken);
        $context = Cache::get($this->tokenCacheKey($token));

        if (!is_array($context) || ($context['email'] ?? '') !== $normalizedEmail) {
            throw new AuthException('Verifica tu correo electrónico antes de crear la cuenta.', 422);
        }

        Cache::forget($this->tokenCacheKey($token));
    }

    /** Envía un código de seis dígitos sin revelar si el correo ya existe. */
    public function requestCourierCode(string $email, bool $isResend = false): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        if ($normalizedEmail === '') {
            throw new AuthException('Ingresa un correo electrónico válido.', 422);
        }

        $cooldown = $this->secondsUntilNextCourierCode($normalizedEmail);
        if ($cooldown > 0) {
            throw new AuthException("Ya enviamos un código. Intenta de nuevo en {$cooldown} segundos.", 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = now()->addSeconds($this->getCodeTtlSeconds());
        Cache::put($this->courierCodeCacheKey($normalizedEmail), [
            'hash' => Hash::make($code),
            'expires_at' => $expiresAt->toISOString(),
        ], $expiresAt);

        if (!$this->sendCourierEmail($normalizedEmail, $code, $expiresAt)) {
            Cache::forget($this->courierCodeCacheKey($normalizedEmail));
            throw new AuthException('No pudimos enviar el código. Inténtalo nuevamente.', 500);
        }

        $seconds = $this->getResendCooldownSeconds();
        Cache::put($this->courierCooldownCacheKey($normalizedEmail), time() + $seconds, now()->addSeconds($seconds));

        return [
            'message' => $isResend ? 'Enviamos un código nuevo.' : 'Te enviamos un código de verificación.',
            'data' => [
                'identifier' => $this->maskEmail($normalizedEmail),
                'expires_in' => $this->getCodeTtlSeconds(),
                'resend_cooldown' => $seconds,
            ],
        ];
    }

    /** Verifica el correo y decide el siguiente paso sin exponerlo antes de probar su propiedad. */
    public function verifyCourierCode(string $email, string $code): array
    {
        $normalizedEmail = $this->normalizeEmail($email);
        $record = Cache::get($this->courierCodeCacheKey($normalizedEmail));
        if (!is_array($record)) {
            throw new AuthException('El código expiró. Solicita uno nuevo.', 410);
        }
        if (!Hash::check(trim($code), (string) ($record['hash'] ?? ''))) {
            throw new AuthException('El código ingresado no es válido.', 422);
        }

        $user = User::query()->where('email', $normalizedEmail)->first();
        $nextStep = !$user
            ? 'register'
            : (in_array($user->role, self::COURIER_ROLES, true) ? 'password' : 'blocked');
        $token = Str::random(64);
        Cache::put($this->courierTokenCacheKey($token), [
            'email' => $normalizedEmail,
            'next_step' => $nextStep,
        ], now()->addSeconds($this->getCodeTtlSeconds()));
        Cache::forget($this->courierCodeCacheKey($normalizedEmail));

        return [
            'verification_token' => $token,
            'next_step' => $nextStep,
            'message' => $nextStep === 'blocked'
                ? 'Esta cuenta debe ingresar desde angelow.online.'
                : null,
        ];
    }

    /** @param array<int, string> $allowedSteps */
    public function assertCourierToken(string $email, string $token, array $allowedSteps): void
    {
        $context = Cache::get($this->courierTokenCacheKey(trim($token)));
        if (!is_array($context)
            || ($context['email'] ?? '') !== $this->normalizeEmail($email)
            || !in_array($context['next_step'] ?? '', $allowedSteps, true)) {
            throw new AuthException('Verifica nuevamente tu correo para continuar.', 422);
        }
    }

    public function consumeCourierToken(string $email, string $token): void
    {
        $this->assertCourierToken($email, $token, ['password', 'register']);
        Cache::forget($this->courierTokenCacheKey(trim($token)));
    }

    /**
     * Envía el correo de verificación con la misma base SMTP del auth-service.
     */
    private function sendRegistrationEmail(string $email, string $code, Carbon $expiresAt): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) config('services.phpmailer.host', 'smtp.gmail.com');
            $mail->Port = (int) config('services.phpmailer.port', 587);
            $mail->CharSet = 'UTF-8';

            $username = trim((string) config('services.phpmailer.username', ''));
            $password = (string) config('services.phpmailer.password', '');
            $mail->SMTPAuth = ($username !== '' && $password !== '');
            if ($mail->SMTPAuth) {
                $mail->Username = $username;
                $mail->Password = $password;
            }

            $mail->SMTPSecure = strtolower((string) config('services.phpmailer.encryption', 'tls')) === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom(
                trim((string) config('services.phpmailer.from_email', 'seguridad@angelow.com')),
                trim((string) config('services.phpmailer.from_name', 'Seguridad Angelow'))
            );
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Tu código para crear la cuenta';
            $mail->Body = $this->buildEmailTemplate($code, $expiresAt->format('d/m/Y H:i'));
            $mail->send();

            return true;
        } catch (MailException $exception) {
            Log::error('Error al enviar código de registro', [
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    private function sendCourierEmail(string $email, string $code, Carbon $expiresAt): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = (string) config('services.phpmailer.host', 'smtp.gmail.com');
            $mail->Port = (int) config('services.phpmailer.port', 587);
            $mail->CharSet = 'UTF-8';
            $username = trim((string) config('services.phpmailer.username', ''));
            $password = (string) config('services.phpmailer.password', '');
            $mail->SMTPAuth = $username !== '' && $password !== '';
            if ($mail->SMTPAuth) {
                $mail->Username = $username;
                $mail->Password = $password;
            }
            $mail->SMTPSecure = strtolower((string) config('services.phpmailer.encryption', 'tls')) === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->setFrom(
                trim((string) config('services.phpmailer.from_email', 'seguridad@angelow.com')),
                trim((string) config('services.phpmailer.from_name', 'Seguridad Angelow')),
            );
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Tu código de acceso a Angelow Repartidor';
            $mail->Body = $this->buildEmailTemplate($code, $expiresAt->format('d/m/Y H:i'));
            $mail->send();

            return true;
        } catch (MailException $exception) {
            Log::error('Error al enviar código de repartidor', [
                'email' => $email,
                'message' => $exception->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Construye un HTML simple y compatible para clientes de correo.
     */
    private function buildEmailTemplate(string $code, string $formattedExpiry): string
    {
        $safeCode = e(trim(chunk_split($code, 1, ' ')));
        $safeExpiry = e($formattedExpiry);

        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Código de verificación</title></head>'
            . '<body style="margin:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#0f172a;">'
            . '<div style="max-width:560px;margin:0 auto;padding:24px;">'
            . '<div style="background:#fff;border-radius:16px;padding:32px;text-align:center;border:1px solid #e5e7eb;">'
            . '<h1 style="margin:0 0 12px;font-size:24px;">Confirma tu correo</h1>'
            . '<p style="font-size:15px;color:#475569;">Usa este código para continuar con tu registro.</p>'
            . '<div style="font-size:34px;font-weight:700;letter-spacing:10px;margin:24px 0;color:#0f172a;">' . $safeCode . '</div>'
            . '<p style="font-size:14px;color:#64748b;">El código vence el <strong>' . $safeExpiry . '</strong>.</p>'
            . '</div></div></body></html>';
    }

    /**
     * Normaliza correos para que caché, validación y registro hablen el mismo idioma.
     */
    private function normalizeEmail(string $email): string
    {
        $normalized = mb_strtolower(trim($email));
        return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : '';
    }

    /**
     * Enmascara el correo en respuestas públicas.
     */
    private function maskEmail(string $email): string
    {
        [$name, $domain] = explode('@', $email, 2);
        return mb_substr($name, 0, 2) . str_repeat('*', max(mb_strlen($name) - 2, 2)) . '@' . $domain;
    }

    /**
     * Controla la espera entre envíos por correo.
     */
    private function secondsUntilNextCode(string $email): int
    {
        $cooldownUntil = (int) Cache::get($this->cooldownCacheKey($email), 0);
        $remaining = $cooldownUntil - time();
        if ($remaining <= 0) {
            Cache::forget($this->cooldownCacheKey($email));
            return 0;
        }

        return min($remaining, $this->getResendCooldownSeconds());
    }

    /**
     * Guarda la ventana anti-spam de envío.
     */
    private function startResendCooldown(string $email): void
    {
        $seconds = $this->getResendCooldownSeconds();
        Cache::put($this->cooldownCacheKey($email), time() + $seconds, now()->addSeconds($seconds));
    }

    private function codeCacheKey(string $email): string
    {
        return 'registration_verification:code:' . sha1($email);
    }

    private function tokenCacheKey(string $token): string
    {
        return 'registration_verification:token:' . $token;
    }

    private function cooldownCacheKey(string $email): string
    {
        return 'registration_verification:cooldown:' . sha1($email);
    }

    private function courierCodeCacheKey(string $email): string
    {
        return 'courier_verification:code:' . sha1($email);
    }

    private function courierTokenCacheKey(string $token): string
    {
        return 'courier_verification:token:' . $token;
    }

    private function courierCooldownCacheKey(string $email): string
    {
        return 'courier_verification:cooldown:' . sha1($email);
    }

    private function secondsUntilNextCourierCode(string $email): int
    {
        $remaining = (int) Cache::get($this->courierCooldownCacheKey($email), 0) - time();
        return max(0, min($remaining, $this->getResendCooldownSeconds()));
    }

    private function getCodeTtlSeconds(): int
    {
        return max(60, (int) config('services.registration_verification.code_ttl', 900));
    }

    private function getResendCooldownSeconds(): int
    {
        return max(30, (int) config('services.registration_verification.resend_cooldown', 60));
    }
}
