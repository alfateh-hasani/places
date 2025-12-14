<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class OwnerRezApiLog extends Model
{
    use CrudTrait;

    protected $table = 'ownerrez_api_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'request_data' => 'array',
            'response_data' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public static function logRequest(
        string $endpoint,
        string $method,
        ?array $requestData,
        ?array $responseData,
        ?int $statusCode,
        ?int $durationMs
    ): void {
        if (! config('ownerrez.log_api_calls')) {
            return;
        }

        static::create([
            'endpoint' => $endpoint,
            'method' => $method,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'created_at' => now(),
        ]);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByEndpoint($query, string $endpoint)
    {
        return $query->where('endpoint', 'like', "%{$endpoint}%");
    }

    public function scopeErrors($query)
    {
        return $query->where('status_code', '>=', 400);
    }
}
