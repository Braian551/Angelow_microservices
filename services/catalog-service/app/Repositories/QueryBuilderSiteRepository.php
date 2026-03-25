<?php

namespace App\Repositories;

use App\Repositories\Contracts\SiteRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Query Builder implementation of the Site repository.
 *
 * Migrated from angelow/index.php and angelow/settings/site_settings.php.
 */
class QueryBuilderSiteRepository implements SiteRepositoryInterface
{
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
        return DB::table('announcements')
            ->where('type', 'top_bar')
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
        return DB::table('announcements')
            ->where('type', 'promo_banner')
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
}
