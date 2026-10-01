<?php

namespace App\Console\Commands;

use App\Support\CssPurger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Rebuilds the website's compiled CSS after view/JS changes:
 *  1. Tailwind (tailwind.config.js) -> public/front/assets/css/tailwind.css
 *  2. Flowbite's precompiled CSS purged to the classes the site uses
 *     -> public/front/assets/vendor/flowbite-2.5.1/flowbite.purged.css
 *
 * Run it whenever Tailwind/Flowbite classes are added to Blade views or
 * front-end JS, otherwise those classes have no styles.
 */
class BuildFrontCss extends Command
{
    protected $signature = 'front:build-css {--skip-tailwind : Only rebuild the purged Flowbite CSS}';

    protected $description = 'Build the website Tailwind CSS and purge unused Flowbite CSS';

    public function handle(): int
    {
        if (! $this->option('skip-tailwind') && ! $this->buildTailwind()) {
            return self::FAILURE;
        }

        $this->purgeFlowbite();

        return self::SUCCESS;
    }

    private function buildTailwind(): bool
    {
        $binary = trim(Process::run('command -v tailwindcss')->output());

        if ($binary === '') {
            $this->error('tailwindcss not found. Install the v3.4.17 standalone CLI or run `npm run build:front`.');

            return false;
        }

        $result = Process::path(base_path())->timeout(300)->run([
            $binary, '-c', 'tailwind.config.js',
            '-i', 'resources/css/front.css',
            '-o', 'public/front/assets/css/tailwind.css',
            '--minify',
        ]);

        if ($result->failed()) {
            $this->error(trim($result->errorOutput()));

            return false;
        }

        $this->info('Tailwind: public/front/assets/css/tailwind.css');

        return true;
    }

    private function purgeFlowbite(): void
    {
        $source = public_path('front/assets/vendor/flowbite-2.5.1/flowbite.min.css');
        $target = public_path('front/assets/vendor/flowbite-2.5.1/flowbite.purged.css');

        $tokens = CssPurger::tokensFromFiles($this->contentFiles());
        $css = CssPurger::purge(File::get($source), $tokens);

        File::put($target, "/* Flowbite 2.5.1, purged by `php artisan front:build-css` */\n".$css);

        $this->info(sprintf('Flowbite: %s (%d KB -> %d KB)', 'flowbite.purged.css', File::size($source) / 1024, File::size($target) / 1024));
    }

    /**
     * Same content Tailwind scans (tailwind.config.js).
     *
     * @return list<string>
     */
    private function contentFiles(): array
    {
        $views = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file): bool => str_ends_with($file->getFilename(), '.blade.php')
                && ! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'backpack'.DIRECTORY_SEPARATOR));

        $php = collect(File::allFiles(resource_path('lang')))->merge(File::allFiles(app_path()))
            ->filter(fn ($file): bool => $file->getExtension() === 'php');

        return $views->merge($php)
            ->map(fn ($file): string => $file->getPathname())
            ->merge(array_map(fn (string $file): string => public_path('front/assets/'.$file), [
                'js/main.js',
                'js/tel.js',
                'js/map.js',
                'js/jquery-searchbox.js',
                'vendor/flowbite-2.5.1/flowbite.min.js',
            ]))
            ->values()
            ->all();
    }
}
