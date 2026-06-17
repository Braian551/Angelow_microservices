<?php

namespace App\Jobs;

use App\Events\NotificationCreated;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Job encolado que dispara el evento broadcast de una notificación.
 * Se ejecuta de forma asíncrona para no bloquear la creación de la notificación
 * y garantiza unicidad por (notificationId, userId) durante 120 segundos.
 */
class DispatchNotificationJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /** Tiempo en segundos durante el cual se garantiza que no haya jobs duplicados. */
    public int $uniqueFor = 120;

    public function __construct(
        public readonly int $notificationId, // ID de la notificación persistida
        public readonly string $userId,       // ID del usuario destinatario
        public readonly array $payload,       // Datos a transmitir por broadcast
    ) {}

    /**
     * Genera un identificador único para el job basado en notificación y usuario.
     * Previene que el mismo evento se encola más de una vez.
     */
    public function uniqueId(): string
    {
        return $this->notificationId . ':' . $this->userId;
    }

    /**
     * Ejecuta el broadcast del evento NotificationCreated al canal privado del usuario.
     */
    public function handle(): void
    {
        broadcast(new NotificationCreated($this->payload, $this->userId));
    }
}
