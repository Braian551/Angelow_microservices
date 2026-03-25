<?php

namespace App\Jobs;

use App\Events\NotificationCreated;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchNotificationJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $uniqueFor = 120;

    public function __construct(
        public readonly int $notificationId,
        public readonly string $userId,
        public readonly array $payload,
    ) {}

    public function uniqueId(): string
    {
        return $this->notificationId . ':' . $this->userId;
    }

    public function handle(): void
    {
        broadcast(new NotificationCreated($this->payload, $this->userId));
    }
}
