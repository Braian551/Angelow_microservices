<?php

namespace App\Services;

use App\Exceptions\AuthException;
use Illuminate\Support\Facades\Http;

/**
 * Servicio reutilizable para validar tokens de verificación de formularios.
 *
 * Centraliza la llamada a Cloudflare y evita que controladores o requests
 * dupliquen lógica sensible de seguridad.
 */
class TurnstileVerificationService
{
    /**
     * Valida un token de formulario antes de ejecutar una operación sensible.
     *
     * @throws AuthException cuando falta configuración, token o Cloudflare rechaza la verificación.
     */
    public function verify(?string $token, ?string $remoteIp = null): void
    {
        $token = trim((string) $token);
        if ($token === '') {
            throw new AuthException(
                'Completa la verificación de seguridad para continuar.',
                422
            );
        }

        $secret = trim((string) config('services.turnstile.secret_key', ''));
        $verifyUrl = trim((string) config('services.turnstile.verify_url', ''));

        if ($secret === '' || $verifyUrl === '') {
            throw new AuthException(
                'No pudimos validar la verificación de seguridad. Inténtalo de nuevo en unos segundos.',
                503
            );
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($remoteIp) {
            $payload['remoteip'] = $remoteIp;
        }

        try {
            // La verificación se realiza contra Cloudflare y falla cerrado ante errores de red.
            $response = Http::asForm()
                ->timeout(8)
                ->post($verifyUrl, $payload);
        } catch (\Throwable) {
            throw new AuthException(
                'No pudimos validar la verificación de seguridad. Inténtalo de nuevo en unos segundos.',
                503
            );
        }

        if (!$response->successful() || $response->json('success') !== true) {
            throw new AuthException(
                'No pudimos validar la verificación de seguridad. Inténtalo de nuevo.',
                422
            );
        }
    }
}
