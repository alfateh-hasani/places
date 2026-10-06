<?php

use App\Models\Apartment;
use App\Models\Blog;
use App\Models\Building;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Unit, building and blog slugs were free text ("A108", "briseh_hotel_alkhobar",
 * "Why-Choose-...-&-Studios"). Rewrite them as lowercase hyphenated slugs and keep every old
 * value as a 301 redirect so indexed and shared links keep working.
 */
return new class extends Migration
{
    /** @var array<string, class-string> */
    private array $tables = [
        'apartments' => Apartment::class,
        'buildings' => Building::class,
        'blogs' => Blog::class,
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->tables as $table => $type) {
            DB::connection('mysql')->table($table)->orderBy('id')->get(['id', 'slug', 'name_en'])->each(function (object $row) use ($table, $type, $now): void {
                $slug = Str::slug($row->slug ?: $row->name_en);

                if ($slug === '' || $slug === $row->slug) {
                    return;
                }

                DB::connection('mysql')->table($table)->where('id', $row->id)->update(['slug' => $slug]);

                DB::connection('mysql')->table('slug_redirects')->updateOrInsert(
                    ['redirectable_type' => $type, 'old_slug' => $row->slug],
                    ['redirectable_id' => $row->id, 'created_at' => $now, 'updated_at' => $now]
                );
            });
        }
    }

    public function down(): void
    {
        // Old slugs stay in slug_redirects; nothing to restore safely.
    }
};
