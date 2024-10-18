<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Get the appropriate column value based on the current language.
     *
     * @param string $columnPrefix The prefix of the column (e.g., 'name').
     * @return mixed The value of the column in the current language.
     */
    public function ml(string $columnPrefix)
    {
        // Get the current locale (e.g., 'ar', 'en')
        $locale = App::getLocale();

        // Generate the column name dynamically (e.g., 'name_ar', 'name_en')
        $column = $columnPrefix . '_' . $locale;

        // Return the value of the column or fallback to another language if empty
        return $this->{$column} ?? $this->{$columnPrefix . '_en'} ?? null;
    }
}
