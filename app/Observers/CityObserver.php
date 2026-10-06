<?php

namespace App\Observers;

use App\Models\City;
use App\Models\SlugRedirect;

class CityObserver
{
    /**
     * Keep the city's old public URL alive by recording a redirect whenever its slug is renamed.
     */
    public function updating(City $city): void
    {
        if (! $city->isDirty('slug')) {
            return;
        }

        $oldSlug = $city->getOriginal('slug');

        if (empty($oldSlug) || $oldSlug === $city->slug) {
            return;
        }

        SlugRedirect::updateOrCreate(
            [
                'redirectable_type' => City::class,
                'old_slug' => $oldSlug,
            ],
            [
                'redirectable_id' => $city->id,
            ]
        );
    }
}
