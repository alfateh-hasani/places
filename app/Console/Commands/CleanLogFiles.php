<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanLogFiles extends Command
{
    protected $signature = 'log:clean {--days=60 : Delete log entries older than this many days}';
    protected $description = 'Remove old entries from laravel.log and delete old daily log files';

    public function handle()
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->cleanLaravelLog($cutoffDate);
        $this->cleanDailyLogs($cutoffDate);

        return Command::SUCCESS;
    }

    private function cleanLaravelLog(Carbon $cutoffDate): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            $this->info('laravel.log not found.');
            return;
        }

        $sizeBefore = filesize($logPath);
        $tempPath = $logPath . '.tmp';
        $reading = fopen($logPath, 'r');
        $writing = fopen($tempPath, 'w');
        $kept = 0;
        $removed = 0;
        $currentEntry = '';
        $currentDate = null;

        while (($line = fgets($reading)) !== false) {
            // Detect start of a new log entry: [2026-02-17 15:20:03]
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2})\s/', $line, $matches)) {
                // Write previous entry if it's recent enough
                if ($currentEntry !== '' && $currentDate !== null) {
                    if ($currentDate >= $cutoffDate->toDateString()) {
                        fwrite($writing, $currentEntry);
                        $kept++;
                    } else {
                        $removed++;
                    }
                }
                $currentEntry = $line;
                $currentDate = $matches[1];
            } else {
                // Continuation of current entry (stack trace, etc.)
                $currentEntry .= $line;
            }
        }

        // Write last entry
        if ($currentEntry !== '' && $currentDate !== null && $currentDate >= $cutoffDate->toDateString()) {
            fwrite($writing, $currentEntry);
            $kept++;
        }

        fclose($reading);
        fclose($writing);

        rename($tempPath, $logPath);

        $sizeAfter = filesize($logPath);
        $freed = $sizeBefore - $sizeAfter;

        $this->info("laravel.log: Kept {$kept} entries, removed {$removed} entries.");
        $this->info('Freed: ' . $this->formatBytes($freed) . " ({$this->formatBytes($sizeBefore)} → {$this->formatBytes($sizeAfter)})");
    }

    private function cleanDailyLogs(Carbon $cutoffDate): void
    {
        $logsPath = storage_path('logs');
        $deleted = 0;

        foreach (glob($logsPath . '/*.log') as $file) {
            $filename = basename($file);

            // Match daily log files like: ownerrez-2026-02-08.log, otp-2026-02-16.log
            if (preg_match('/(\d{4}-\d{2}-\d{2})\.log$/', $filename, $matches)) {
                if ($matches[1] < $cutoffDate->toDateString()) {
                    unlink($file);
                    $deleted++;
                    $this->line("Deleted: {$filename}");
                }
            }
        }

        if ($deleted > 0) {
            $this->info("Deleted {$deleted} old daily log files.");
        } else {
            $this->info('No old daily log files to delete.');
        }
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
