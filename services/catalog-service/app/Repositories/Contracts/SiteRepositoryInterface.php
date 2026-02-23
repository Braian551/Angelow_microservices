<?php

namespace App\Repositories\Contracts;

/**
 * Contract for Site content repository.
 *
 * Handles site settings, sliders, and announcements.
 */
interface SiteRepositoryInterface
{
    /**
     * Get all site settings as key-value.
     */
    public function getSettings(): array;

    /**
     * Get active sliders ordered by position.
     */
    public function getSliders(): array;

    /**
     * Get the active top-bar announcement.
     */
    public function getTopBarAnnouncement(): ?object;

    /**
     * Get the active promo banner announcement.
     */
    public function getPromoBanner(): ?object;
}
