<?php

namespace App\Services;

use App\Repositories\Contracts\SiteRepositoryInterface;

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
        return [
            'settings'       => $this->siteRepository->getSettings(),
            'sliders'        => $this->siteRepository->getSliders(),
            'top_bar'        => $this->siteRepository->getTopBarAnnouncement(),
            'promo_banner'   => $this->siteRepository->getPromoBanner(),
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
}
