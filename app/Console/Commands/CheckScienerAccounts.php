<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\LockCredentials;
use Illuminate\Console\Command;

class CheckScienerAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sciener:check-accounts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Sciener login for every building with TTLock credentials configured';

    public function __construct(private readonly LockProviderInterface $provider)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $buildings = Building::whereNotNull('ttlock_username')
            ->withCount([
                'apartments',
                'apartments as apartments_with_lock_count' => fn ($q) => $q->whereNotNull('smart_lock_id'),
            ])
            ->get();

        if ($buildings->isEmpty()) {
            $this->info('No buildings with a TTLock username configured.');

            return self::SUCCESS;
        }

        $rows = $buildings->map(function (Building $building) {
            $result = $this->provider->testConnection(new LockCredentials(
                lockId: '',
                username: (string) $building->ttlock_username,
                password: (string) $building->ttlock_password,
            ));

            return [
                $building->id,
                $building->name_ar,
                $building->ttlock_username,
                $building->apartments_with_lock_count.'/'.$building->apartments_count,
                $result->ok ? '✅ OK' : '❌ FAILED',
                $result->ok ? '' : "[{$result->vendorErrorCode}] {$result->message}",
            ];
        });

        $this->table(['ID', 'Building', 'Sciener Username', 'Apts w/ Lock', 'Status', 'Error'], $rows->all());

        $failed = $rows->filter(fn ($row) => $row[4] === '❌ FAILED')->count();
        $this->line("\n{$failed} of {$rows->count()} accounts failed to authenticate.");

        return self::SUCCESS;
    }
}
