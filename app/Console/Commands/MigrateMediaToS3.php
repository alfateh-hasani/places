<?php

namespace App\Console\Commands;

use App\Support\Media\DomainPathGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One-time bulk migration: for every media row, finds its physical file on the
 * LOCAL disk (old flat `{id}/…` layout), uploads it to S3 at the new
 * DomainPathGenerator path (byte-for-byte — no reprocessing), then sets the
 * row's disk to `s3`.
 *
 * Source is the local filesystem, NOT the row's `disk` column — because rows may
 * already claim `disk=s3` while the real files still live locally.
 *
 * Built to survive real bulk runs:
 *  - Resumable    : skips objects already on S3, so Ctrl+C then re-run continues.
 *  - Duplicate-safe: never re-uploads an object that is already there.
 *  - Failure-safe : failed files retried with backoff; disk flip only after all
 *                   of a row's files land, so the next run retries the rest.
 *  - Throttle-safe: exponential backoff + jitter on S3 SlowDown / transient errors.
 *  - Reports      : lists media whose source file is missing locally (nothing to upload).
 */
class MigrateMediaToS3 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:migrate-to-s3
        {--dry-run : List what would be copied without copying or touching the DB}
        {--delete-local : Delete local files after a successful, verified copy}
        {--source-disk=public : Local disk to read the original files from}
        {--attempts=5 : Max upload attempts per file before giving up}
        {--sleep-ms=0 : Optional pause between files to stay under S3 rate limits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upload local media files to S3 (new folder layout) and switch their disk to s3';

    private const TARGET_DISK = 's3';

    public function handle(DomainPathGenerator $generator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $deleteLocal = (bool) $this->option('delete-local');
        $maxAttempts = max(1, (int) $this->option('attempts'));
        $sleepMs = max(0, (int) $this->option('sleep-ms'));

        // Local disks to search for the source files (old flat {id}/… layout).
        $sourceDisks = array_values(array_unique([(string) $this->option('source-disk'), 'public', 'local']));

        $copied = 0;
        $alreadyThere = 0;
        $migratedRows = 0;
        $alreadyOnS3 = 0;
        $missingSource = 0;
        $failed = 0;
        $missing = [];
        $failures = [];

        $total = Media::count();
        if ($total === 0) {
            $this->info('No media records to migrate.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (Media::cursor() as $media) {
            /** @var Media $media */
            $bar->advance();

            $oldDir = (string) $media->getKey();
            $newDir = rtrim($generator->getPath($media), '/');

            // 1) Locate the source file(s) on a local disk (old flat layout).
            $local = null;
            $files = [];
            foreach ($sourceDisks as $diskName) {
                $disk = Storage::disk($diskName);
                $found = $disk->allFiles($oldDir);
                if ($found !== []) {
                    $local = $disk;
                    $files = $found;
                    break;
                }
            }

            // 2) No local source — either already migrated to S3, or genuinely missing.
            if ($files === []) {
                if (Storage::disk(self::TARGET_DISK)->exists($newDir.'/'.$media->file_name)) {
                    $alreadyOnS3++;
                } else {
                    $missingSource++;
                    $missing[] = "#{$media->getKey()} ({$media->file_name})";
                }

                continue;
            }

            // 3) Upload each source file to its new S3 path.
            $allOk = true;

            foreach ($files as $file) {
                $dest = $newDir.substr($file, strlen($oldDir));

                if ($dryRun) {
                    $this->newLine();
                    $this->line("would upload: {$file}  ->  [s3] {$dest}");
                    $copied++;

                    continue;
                }

                if (Storage::disk(self::TARGET_DISK)->exists($dest)) {
                    $alreadyThere++;

                    continue;
                }

                if ($this->copyWithRetry($local, $file, $dest, $maxAttempts)) {
                    $copied++;
                } else {
                    $allOk = false;
                    $failed++;
                    $failures[] = "{$file} -> {$dest}";
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            if ($dryRun || ! $allOk) {
                continue;
            }

            // 4) Point the row at S3 (raw update — no model events / no file moves).
            DB::table($media->getTable())
                ->where('id', $media->getKey())
                ->update(['disk' => self::TARGET_DISK, 'conversions_disk' => self::TARGET_DISK]);
            $migratedRows++;

            if ($deleteLocal) {
                foreach ($files as $file) {
                    $local->delete($file);
                }
            }
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("DRY RUN — would upload {$copied} file(s). Already on S3: {$alreadyOnS3}. Missing local source: {$missingSource}.");
        } else {
            $this->info("Uploaded {$copied} · already on s3 {$alreadyThere} · rows migrated {$migratedRows} · rows already on s3 {$alreadyOnS3} · missing source {$missingSource} · failed {$failed}.");
        }

        if ($missing !== []) {
            $this->newLine();
            $this->warn('Media with NO local source file (fetch these from production storage):');
            foreach (array_slice($missing, 0, 50) as $m) {
                $this->line("  {$m}");
            }
            if (count($missing) > 50) {
                $this->line('  … '.(count($missing) - 50).' more');
            }
        }

        if ($failed > 0) {
            $this->newLine();
            $this->error('Failed uploads (safe to re-run — it resumes where it left off):');
            foreach (array_slice($failures, 0, 50) as $f) {
                $this->line("  {$f}");
            }

            return self::FAILURE;
        }

        if (! $dryRun && ! $deleteLocal && $copied > 0) {
            $this->line('Local files kept. Re-run with --delete-local after verifying S3.');
        }

        return self::SUCCESS;
    }

    /**
     * Copy one file local → s3 with exponential backoff + jitter on transient
     * / throttling errors (S3 SlowDown, 503, network blips).
     */
    private function copyWithRetry(
        \Illuminate\Contracts\Filesystem\Filesystem $local,
        string $file,
        string $dest,
        int $maxAttempts,
    ): bool {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $stream = $local->readStream($file);
                Storage::disk(self::TARGET_DISK)->writeStream($dest, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                return true;
            } catch (\Throwable $e) {
                if ($attempt >= $maxAttempts) {
                    $this->newLine();
                    $this->error("failed after {$attempt} attempt(s): {$file} ({$e->getMessage()})");

                    return false;
                }

                $delayMs = (int) (500 * (2 ** ($attempt - 1))) + random_int(0, 250);
                $this->newLine();
                $this->warn("retry {$attempt}/{$maxAttempts} in {$delayMs}ms: {$file} ({$e->getMessage()})");
                usleep($delayMs * 1000);
            }
        }
    }
}
