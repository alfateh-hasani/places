<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\ScienerToken;
use App\Services\Locks\Contracts\LockProviderInterface;
use App\Services\Locks\LockCredentials;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshScienerTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sciener:refresh-tokens {--within=1 : Refresh tokens expiring within this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Proactively re-authenticate Sciener accounts whose token is expired or about to expire';

    public function __construct(private readonly LockProviderInterface $provider)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $threshold = now()->addDays((int) $this->option('within'));

        $buildings = Building::whereNotNull('ttlock_username')->get();

        $refreshed = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($buildings as $building) {
            $token = ScienerToken::where('username', $building->ttlock_username)->first();

            // Still has plenty of life left — nothing to do yet.
            if ($token && $token->expires_at && $token->expires_at->isAfter($threshold)) {
                $skipped++;

                continue;
            }

            $result = $this->provider->testConnection(new LockCredentials(
                lockId: '',
                username: (string) $building->ttlock_username,
                password: (string) $building->ttlock_password,
            ));

            if ($result->ok) {
                Log::info('sciener.token_refresh.success', [
                    'building_id' => $building->id,
                    'username' => $building->ttlock_username,
                ]);
                $refreshed++;
            } else {
                Log::warning('sciener.token_refresh.failed', [
                    'building_id' => $building->id,
                    'username' => $building->ttlock_username,
                    'vendor_error_code' => $result->vendorErrorCode,
                    'message' => $result->message,
                ]);
                $failed++;
            }
        }

        $this->info("Refreshed: {$refreshed}, Failed: {$failed}, Skipped (still valid): {$skipped}");

        return self::SUCCESS;
    }
}
