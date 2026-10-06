<?php

namespace App\Traits;

use App\Services\Seo\SeoService;

/**
 * Customer-area pages (account, bookings, favourites) are private, so they get the
 * standard tags but are kept out of the search index.
 */
trait generateSeoTrait
{
    public function generateSeo(?string $seo_title, ?string $seo_description, string $url, ?string $image = null): void
    {
        app(SeoService::class)
            ->page($seo_title, $seo_description, $url, $image)
            ->noIndex();
    }
}
