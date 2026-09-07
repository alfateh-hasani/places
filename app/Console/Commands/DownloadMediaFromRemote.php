<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Downloads media originals (and optionally their conversions) from the live
 * site into the local `public` disk in the old flat `{id}/{file}` layout, so
 * `media:migrate-to-s3` can then push them to the bucket.
 *
 * Use when you can reach the files over HTTP (they still load on the live site)
 * but not the server's filesystem / cPanel.
 *
 * The URL is built as:  {base}/{media_id}/{file_name}
 * e.g. --base=https://dyafa.sa/storage  ->  https://dyafa.sa/storage/1006/photo.jpg
 * Confirm the pattern first by right-clicking an image on the live site and
 * copying its address.
 */
class DownloadMediaFromRemote extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:download-remote
        {--base= : Base URL that precedes /<id>/<file_name> (e.g. https://dyafa.sa/storage)}
        {--only-missing : Skip media whose local file already exists}
        {--skip-conversions : Do NOT download generated image conversions (thumbnails)}
        {--attempts=3 : Max download attempts per file}
        {--timeout=60 : HTTP timeout per file (seconds)}
        {--dry-run : List the URLs that would be fetched without downloading}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download media files from the live site into local storage (flat layout)';

    public function handle(): int
    {
        $base = rtrim((string) $this->option('base'), '/');

        if ($base === '') {
            $this->error('Provide --base, e.g. --base=https://dyafa.sa/storage');
            $this->line('Tip: right-click an image on the live site → "Copy image address" to find the exact pattern.');

            return self::INVALID;
        }

        $onlyMissing = (bool) $this->option('only-missing');
        $withConversions = ! (bool) $this->option('skip-conversions');
        $dryRun = (bool) $this->option('dry-run');
        $maxAttempts = max(1, (int) $this->option('attempts'));
        $timeout = max(5, (int) $this->option('timeout'));

        $local = Storage::disk('public');

        $downloaded = 0;
        $skipped = 0;
        $failed = 0;
        $failures = [];

        $total = Media::count();
        if ($total === 0) {
            $this->info('No media records.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (Media::cursor() as $media) {
            /** @var Media $media */
            $bar->advance();

            $id = (string) $media->getKey();
            $fileName = (string) $media->file_name;
            $localPath = "{$id}/{$fileName}";

            if ($onlyMissing && $local->exists($localPath)) {
                $skipped++;

                continue;
            }

            $url = "{$base}/{$id}/".rawurlencode($fileName);

            if ($dryRun) {
                $this->newLine();
                $this->line("would fetch: {$url}");
                $downloaded++;

                continue;
            }

            if ($this->fetch($url, $local, $localPath, $maxAttempts, $timeout)) {
                $downloaded++;
            } else {
                $failed++;
                $failures[] = "#{$id} {$url}";

                continue;
            }

            if ($withConversions) {
                $this->fetchConversions($media, $base, $local, $maxAttempts, $timeout);
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Downloaded {$downloaded} · skipped (already local) {$skipped} · failed {$failed}.");

        if ($failed > 0) {
            $this->newLine();
            $this->warn('Could not download (missing on live site / wrong --base?):');
            foreach (array_slice($failures, 0, 50) as $f) {
                $this->line("  {$f}");
            }
            if (count($failures) > 50) {
                $this->line('  … '.(count($failures) - 50).' more');
            }

            return self::FAILURE;
        }

        $this->line('Next: php artisan media:migrate-to-s3');

        return self::SUCCESS;
    }

    private function fetchConversions(
        Media $media,
        string $base,
        \Illuminate\Contracts\Filesystem\Filesystem $local,
        int $maxAttempts,
        int $timeout,
    ): void {
        $generated = $media->generated_conversions;

        if (! is_array($generated)) {
            return;
        }

        $name = pathinfo((string) $media->file_name, PATHINFO_FILENAME);
        $ext = pathinfo((string) $media->file_name, PATHINFO_EXTENSION);
        $id = (string) $media->getKey();

        // Conversions are usually re-encoded to webp (->format('webp')); a few keep
        // the original extension (e.g. Onboarding thumb). Try webp first, then original.
        $candidateExts = array_values(array_unique(array_filter(['webp', $ext])));

        foreach ($generated as $conversion => $done) {
            if (! $done) {
                continue;
            }

            foreach ($candidateExts as $cext) {
                $convFile = "{$name}-{$conversion}.{$cext}";
                $url = "{$base}/{$id}/conversions/".rawurlencode($convFile);
                // Best-effort — a 404 just means the wrong extension; try the next one.
                // Anything still missing can be rebuilt with `php artisan media-library:regenerate`.
                if ($this->fetch($url, $local, "{$id}/conversions/{$convFile}", $maxAttempts, $timeout, true)) {
                    break;
                }
            }
        }
    }

    private function fetch(
        string $url,
        \Illuminate\Contracts\Filesystem\Filesystem $local,
        string $localPath,
        int $maxAttempts,
        int $timeout,
        bool $quiet = false,
    ): bool {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = Http::timeout($timeout)->get($url);

                if ($response->successful()) {
                    $local->put($localPath, $response->body());

                    return true;
                }

                if ($response->status() === 404) {
                    if (! $quiet) {
                        $this->newLine();
                        $this->warn("404 not found: {$url}");
                    }

                    return false;
                }

                throw new \RuntimeException("HTTP {$response->status()}");
            } catch (\Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    if (! $quiet) {
                        $this->newLine();
                        $this->error("failed after {$attempt}x: {$url} ({$e->getMessage()})");
                    }

                    return false;
                }

                $delayMs = (int) (500 * (2 ** ($attempt - 1))) + random_int(0, 250);
                usleep($delayMs * 1000);
            }
        }
    }
}
