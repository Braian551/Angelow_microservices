<?php

namespace App\Repositories;

use App\Repositories\Contracts\SiteRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Query Builder implementation of the Site repository.
 *
 * Migrated from angelow/index.php and angelow/settings/site_settings.php.
 */
class QueryBuilderSiteRepository implements SiteRepositoryInterface
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    /**
     * Get all site settings as key-value pairs.
     *
     * Migrated from fetch_site_settings() in site_settings.php
     */
    public function getSettings(): array
    {
        $rows = DB::table('site_settings')
            ->select(['setting_key', 'setting_value'])
            ->get();

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }

        return $settings;
    }

    /**
     * Get active sliders ordered by position.
     *
     * Migrated from index.php: SELECT * FROM sliders WHERE is_active = 1 ORDER BY order_position ASC
     */
    public function getSliders(): array
    {
        return DB::table('sliders')
            ->where('is_active', true)
            ->orderBy('order_position')
            ->get()
            ->map(fn($s) => (array) $s)
            ->toArray();
    }

    /**
     * Get the active top-bar announcement (highest priority).
     *
     * Migrated from index.php top_bar_query.
     */
    public function getTopBarAnnouncement(): ?object
    {
        return $this->announcementsQuery()
            ->whereIn('type', ['top_bar', 'bar'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Get the active promo banner announcement (highest priority).
     *
     * Migrated from index.php promo_banner_query.
     */
    public function getPromoBanner(): ?object
    {
        return $this->announcementsQuery()
            ->whereIn('type', ['promo_banner', 'banner'])
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * Durante la migracion, prioriza la fuente legacy usada por admin anuncios.
     */
    private function announcementsQuery()
    {
        if ($this->legacyAnnouncementsAvailable()) {
            return DB::connection(self::LEGACY_CONNECTION)->table('announcements');
        }

        return DB::table('announcements');
    }

    private function legacyAnnouncementsAvailable(): bool
    {
        try {
            return Schema::connection(self::LEGACY_CONNECTION)->hasTable('announcements');
        } catch (\Throwable) {
            return false;
        }
    }
}
