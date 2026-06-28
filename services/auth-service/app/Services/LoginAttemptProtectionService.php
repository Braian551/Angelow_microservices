<?php

namespace App\Services;

use App\Models\AuthLoginAttempt;
use Carbon\CarbonInterface;

/**
 * Servicio de protección para el inicio de sesión nativo.
 *
 * Reutiliza una tabla agregada por credencial/IP para decidir cuándo exigir
 * verificación adicional y cuándo aplicar un bloqueo temporal.
 */
class LoginAttemptProtectionService
{
    /**
     * Devuelve el estado actual de protección para una credencial e IP.
     */
    public function status(string $credential, string $ipAddress): array
    {
        $record = $this->findRecord($credential, $ipAddress);
        if (!$record) {
            return $this->buildStatus(0, null);
        }

        $blockedUntil = $record->blocked_until;
        if ($blockedUntil && $blockedUntil->isPast()) {
            $record->forceFill([
                'failed_attempts' => 0,
                'blocked_until' => null,
            ])->save();

            return $this->buildStatus(0, null);
        }

        return $this->buildStatus($record->failed_attempts, $blockedUntil);
    }

    /**
     * Registra un fallo real de credenciales y recalcula el estado de protección.
     */
    public function recordFailure(string $credential, string $ipAddress): array
    {
        $normalizedCredential = $this->normalizeCredential($credential);
        $record = AuthLoginAttempt::query()->firstOrNew([
            'credential' => $normalizedCredential,
            'ip_address' => $this->normalizeIp($ipAddress),
        ]);

        $record->failed_attempts = ((int) $record->failed_attempts) + 1;
        $record->last_failed_at = now();

        if ($record->failed_attempts >= $this->blockAfterAttempts()) {
            $record->blocked_until = now()->addMinutes($this->blockMinutes());
        }

        $record->save();

        return $this->status($credential, $ipAddress);
    }

    /**
     * Limpia el contador cuando el acceso nativo fue exitoso.
     */
    public function clear(string $credential, string $ipAddress): void
    {
        $record = $this->findRecord($credential, $ipAddress);
        if (!$record) {
            return;
        }

        $record->forceFill([
            'failed_attempts' => 0,
            'blocked_until' => null,
        ])->save();
    }

    /**
     * Busca el registro agregado por credencial normalizada e IP.
     */
    private function findRecord(string $credential, string $ipAddress): ?AuthLoginAttempt
    {
        return AuthLoginAttempt::query()
            ->where('credential', $this->normalizeCredential($credential))
            ->where('ip_address', $this->normalizeIp($ipAddress))
            ->first();
    }

    /**
     * Construye la respuesta común consumida por el controlador.
     */
    private function buildStatus(int $failedAttempts, ?CarbonInterface $blockedUntil): array
    {
        return [
            'failed_attempts' => $failedAttempts,
            'captcha_required' => $failedAttempts >= $this->captchaAfterAttempts(),
            'blocked_until' => $blockedUntil?->toISOString(),
            'is_blocked' => $blockedUntil !== null && $blockedUntil->isFuture(),
        ];
    }

    /**
     * Normaliza correo o teléfono para no crear registros duplicados por formato.
     */
    private function normalizeCredential(string $credential): string
    {
        $value = mb_strtolower(trim($credential));
        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $value;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        return $digits !== '' ? $digits : $value;
    }

    /**
     * Normaliza la IP guardada y evita valores vacíos.
     */
    private function normalizeIp(string $ipAddress): string
    {
        $value = trim($ipAddress);
        return $value !== '' ? $value : '0.0.0.0';
    }

    private function captchaAfterAttempts(): int
    {
        return max(1, (int) config('services.login_protection.captcha_after_attempts', 3));
    }

    private function blockAfterAttempts(): int
    {
        return max($this->captchaAfterAttempts() + 1, (int) config('services.login_protection.temp_block_after_attempts', 8));
    }

    private function blockMinutes(): int
    {
        return max(1, (int) config('services.login_protection.temp_block_minutes', 15));
    }
}
