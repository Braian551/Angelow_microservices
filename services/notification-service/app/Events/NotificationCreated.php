<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se emite cuando se crea una notificación.
 * Se transmite por WebSocket a un canal privado del usuario destino
 * para que el frontend reciba la notificación en tiempo real.
 */
class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $payload, // Contenido de la notificación (título, mensaje, tipo, etc.)
        public readonly string $userId, // ID del usuario al que va dirigida la notificación
    ) {}

    /**
     * Define el canal privado por usuario para la transmisión del evento.
     * Solo el usuario autenticado con ese ID podrá escuchar el canal.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("users.{$this->userId}")];
    }

    /**
     * Nombre del evento en el canal de broadcasting.
     * El frontend se suscribe a 'notification.created' para reaccionar.
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    /**
     * Datos que se envían junto con el evento broadcast.
     * Coincide con el payload original de la notificación.
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
