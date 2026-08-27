<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\LockCredentials;
use Illuminate\Console\Command;

class VerifyLockOwnership extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sciener:verify-lock-ownership {building? : Building ID, or all buildings if omitted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Check whether each building's Sciener account actually administers the locks assigned to it";

    public function __construct(private readonly LockProviderInterface $provider)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $buildings = Building::whereNotNull('ttlock_username')
            ->when($this->argument('building'), fn ($q, $id) => $q->whereKey($id))
            ->with('apartments.smartLock')
            ->get();

        if ($buildings->isEmpty()) {
            $this->info('No matching buildings with a TTLock username configured.');

            return self::SUCCESS;
        }

        $mismatches = 0;

        foreach ($buildings as $building) {
            $localLockIds = $building->apartments
                ->pluck('smartLock.lock_id')
                ->filter()
                ->unique()
                ->values();

            if ($localLockIds->isEmpty()) {
                continue;
            }

            $remoteLockIds = collect($this->provider->listManagedLockIds(new LockCredentials(
                lockId: '',
                username: (string) $building->ttlock_username,
                password: (string) $building->ttlock_password,
            )));

            $missing = $localLockIds->diff($remoteLockIds);

            if ($missing->isEmpty()) {
                $this->info("✅ {$building->name_ar}: all {$localLockIds->count()} assigned locks are administered by {$building->ttlock_username}");

                continue;
            }

            $mismatches++;
            $this->error("❌ {$building->name_ar}: {$missing->count()} lock(s) NOT found under {$building->ttlock_username} — {$missing->implode(', ')}");
        }

        $this->line("\n{$mismatches} building(s) have a lock/account mismatch.");

        return self::SUCCESS;
    }
}
