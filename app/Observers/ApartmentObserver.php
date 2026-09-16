<?php

namespace App\Observers;

use App\Models\Apartment;
use App\Models\SlugRedirect;

class ApartmentObserver
{
    /**
     * Keep the unit's old public URL alive by recording a redirect whenever its slug is renamed.
     */
    public function updating(Apartment $apartment): void
    {
        if (! $apartment->isDirty('slug')) {
            return;
        }

        $oldSlug = $apartment->getOriginal('slug');

        if (empty($oldSlug) || $oldSlug === $apartment->slug) {
            return;
        }

        SlugRedirect::updateOrCreate(
            [
                'redirectable_type' => Apartment::class,
                'old_slug' => $oldSlug,
            ],
            [
                'redirectable_id' => $apartment->id,
            ]
        );
    }
}
