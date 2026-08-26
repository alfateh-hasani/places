<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PasscodeRetryAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'apartment_id',
        'customer_id',
        'operation',
        'attempt_count',
        'max_attempts',
        'last_attempt_at',
        'next_attempt_at',
        'last_error',
        'status',
        'attempt_history',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'next_attempt_at' => 'datetime',
        'attempt_history' => 'array',
    ];

    // العلاقات
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReadyForRetry($query)
    {
        return $query->where('status', 'pending')
                    ->where('next_attempt_at', '<=', now())
                    ->whereColumn('attempt_count', '<', 'max_attempts');
    }

    public function scopeMaxAttemptsReached($query)
    {
        return $query->where('status', 'max_attempts_reached');
    }

    // الدوال المساعدة
    public function canRetry()
    {
        return $this->attempt_count < $this->max_attempts && 
               $this->status === 'pending' && 
               ($this->next_attempt_at === null || $this->next_attempt_at <= now());
    }

    public function markAsInProgress()
    {
        $this->update([
            'status' => 'in_progress',
            'last_attempt_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
        ]);
    }

    public function markAsFailed($error = null)
    {
        $this->increment('attempt_count');
        
        // إضافة المحاولة إلى التاريخ
        $history = $this->attempt_history ?? [];
        $history[] = [
            'attempt' => $this->attempt_count,
            'timestamp' => now()->toISOString(),
            'error' => $error,
        ];

        $status = $this->attempt_count >= $this->max_attempts ? 'max_attempts_reached' : 'pending';
        $nextAttemptAt = $this->attempt_count < $this->max_attempts
            ? now()->addMinutes(config('locks.retry_backoff_minutes', 20))
            : null;

        $this->update([
            'status' => $status,
            'last_error' => $error,
            'next_attempt_at' => $nextAttemptAt,
            'attempt_history' => $history,
        ]);
    }

    // إنشاء أو تحديث محاولة
    public static function createOrUpdateForBooking($booking, $error = null, string $operation = 'provision')
    {
        $attempt = self::firstOrCreate(
            ['booking_id' => $booking->id, 'operation' => $operation],
            [
                'apartment_id' => $booking->apartment_id,
                'customer_id' => $booking->customer_id,
                'attempt_count' => 0,
                'max_attempts' => config('locks.max_retry_attempts', 5),
                'status' => 'pending',
                'next_attempt_at' => now()->addMinutes(config('locks.retry_backoff_minutes', 20)),
            ]
        );

        if ($error) {
            $attempt->markAsFailed($error);
        }

        return $attempt;
    }

    // الحصول على المحاولات الجاهزة لإعادة المحاولة
    public static function getReadyForRetry(?string $operation = null)
    {
        return self::readyForRetry()
            ->when($operation, fn ($query) => $query->where('operation', $operation))
            ->with(['booking', 'apartment', 'customer'])
            ->get();
    }
} 