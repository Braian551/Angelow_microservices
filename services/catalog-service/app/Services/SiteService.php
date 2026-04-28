<?php

namespace App\Services;

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

    public function getSettings(): array
    {
        return $this->siteRepository->getSettings();
    }

    public function getSliders(): array
    {
        return $this->siteRepository->getSliders();
    }

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

    private function resolveNotificationHomeAnnouncementsEndpoint(): string
    {
        $baseUrl = rtrim((string) config('services.notification.base_url', 'http://notification-service:8000/api'), '/');

        if (str_ends_with($baseUrl, '/api')) {
            return $baseUrl . '/announcements/home';
        }

        return $baseUrl . '/api/announcements/home';
    }
}
