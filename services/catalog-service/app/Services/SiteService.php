<?php

namespace App\Services;

// Comentario de mantenimiento: Este servicio concentra reglas de negocio para que los controladores no dupliquen lógica.

use App\Repositories\Contracts\SiteRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Site Service
 *
 * Business logic for site-wide content: settings, sliders, announcements.
 */
class SiteService
{
    /**
     * Explica la intención de __construct dentro del flujo del servicio.
     */
    public function __construct(
        private readonly SiteRepositoryInterface $siteRepository,
    ) {}

    /**
     * Get all data needed for the home page in a single call.
     */
    public function getHomeData(): array
    {
        $remoteAnnouncements = $this->fetchHomeAnnouncementsFromNotification();

        return [
            'settings'       => $this->siteRepository->getSettings(),
            'sliders'        => $this->siteRepository->getSliders(),
            'top_bar'        => $remoteAnnouncements['top_bar'] ?? $this->siteRepository->getTopBarAnnouncement(),
            'promo_banner'   => $remoteAnnouncements['promo_banner'] ?? $this->siteRepository->getPromoBanner(),
        ];
    }

    /**
     * Carga configuraciones persistidas y las transforma a un mapa por clave.
     */

    public function getSettings(): array
    {
        return $this->siteRepository->getSettings();
    }

    /**
     * Carga sliders activos ordenados para la portada.
     */

    public function getSliders(): array
    {
        return $this->siteRepository->getSliders();
    }

    /**
     * Consulta anuncios del servicio de notificaciones como fuente preferente.
     */

    private function fetchHomeAnnouncementsFromNotification(): ?array
    {
        $endpoint = $this->resolveNotificationHomeAnnouncementsEndpoint();

        try {
            $response = Http::acceptJson()
                ->timeout(5)
                ->get($endpoint);

            if (!$response->successful()) {
                return null;
            }

            $payload = $response->json('data');
            if (!is_array($payload)) {
                return null;
            }

            return [
                'top_bar' => is_array($payload['top_bar'] ?? null) ? $payload['top_bar'] : null,
                'promo_banner' => is_array($payload['promo_banner'] ?? null) ? $payload['promo_banner'] : null,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Construye la URL interna para anuncios visibles en la portada.
     */

    private function resolveNotificationHomeAnnouncementsEndpoint(): string
    {
        $baseUrl = rtrim((string) config('services.notification.base_url', 'http://notification-service:8000/api'), '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/announcements/home';
        }

        return $baseUrl . '/api/announcements/home';
    }
}
