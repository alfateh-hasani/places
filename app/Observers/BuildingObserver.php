<?php

namespace App\Observers;

use App\Models\Building;
use App\Models\SlugRedirect;

class BuildingObserver
{
    /**
     * Keep the building's old public URL alive by recording a redirect whenever its slug is renamed.
     */
    public function updating(Building $building): void
    {
        if (! $building->isDirty('slug')) {
            return;
        }

        $oldSlug = $building->getOriginal('slug');

        if (empty($oldSlug) || $oldSlug === $building->slug) {
            return;
        }

        SlugRedirect::updateOrCreate(
            [
                'redirectable_type' => Building::class,
                'old_slug' => $oldSlug,
            ],
            [
                'redirectable_id' => $building->id,
            ]
        );
    }
}
