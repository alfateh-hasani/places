<?php

use App\Models\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * City slugs were typed by hand ("Al Madinah", "Jeddah"), which produced %20-encoded,
 * mixed-case public URLs. Rewrite them as lowercase hyphenated slugs and keep the old
 * ones as 301 redirects so already indexed/shared city links keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::connection('mysql')->table('cities')->orderBy('id')->get()->each(function (object $city) use ($now): void {
            $slug = Str::slug($city->slug ?: $city->name_en);

            if ($slug === '' || $slug === $city->slug) {
                return;
            }

            DB::connection('mysql')->table('cities')->where('id', $city->id)->update(['slug' => $slug]);

            DB::connection('mysql')->table('slug_redirects')->updateOrInsert(
                ['redirectable_type' => City::class, 'old_slug' => $city->slug],
                ['redirectable_id' => $city->id, 'created_at' => $now, 'updated_at' => $now]
            );
        });
    }

    public function down(): void
    {
        // Old slugs stay in slug_redirects; nothing to restore safely.
    }
};
