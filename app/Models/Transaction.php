<?php

namespace App\Models;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Transaction extends Model implements HasMedia
{
    use CrudTrait, InteractsWithMedia, LogsActivity;

    protected $connection = 'mysql';

    protected $guarded = [];

    protected $casts = [
        'order_id' => 'string',
    ];

    /** Bank-transfer receipt uploaded by staff for a manual (dashboard) booking. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('receipt')->singleFile();
    }

    /**
     * Signed, expiring URL for the (private) bank-transfer receipt.
     * Falls back to a plain URL on non-S3 disks (e.g. local dev).
     */
    public function receiptUrl(int $minutes = 15): ?string
    {
        $media = $this->getFirstMedia('receipt');

        if (! $media) {
            return null;
        }

        $driver = config("filesystems.disks.{$media->disk}.driver");

        return $driver === 's3'
            ? $media->getTemporaryUrl(now()->addMinutes($minutes))
            : $media->getUrl();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }


    public function apartment(): BelongsTo
    {
        return $this->belongsTo(apartment::class);
    }

    //booking
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
