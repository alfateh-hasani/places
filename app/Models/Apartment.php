<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Cache;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Apartment extends Model implements HasMedia
{
    use CrudTrait;
    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;

    protected $connection = 'mysql';

    protected $with = ['media', 'building'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'building_id' => 'integer',
        'num_rooms' => 'integer',
        'num_beds' => 'integer',
        'area' => 'decimal:2',
        'is_active' => 'boolean',
        'smart_lock_id' => 'integer',
        'price' => 'decimal:2',
        'bathrooms_count' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        // log all changes
        return LogOptions::defaults()
            ->logAll();

    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    // features
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'apartment_features', 'apartment_id', 'feature_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image');
        $this->addMediaCollection('video');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('grid')
            ->fit(Fit::Crop, 364, desiredHeight: 300)

            ->format('webp')                         // Convert to WebP format
            ->nonQueued();                           // Process synchronously (optional)

        $this->addMediaConversion('grid-app2')
            ->fit(Fit::Crop, 660, desiredHeight: 432)
            ->quality(75)
            ->format('webp')
            ->nonQueued();

    }

    // belongsTo polices
    public function policy()
    {
        return $this->belongsTo(Policy::class);
    }

    // lock_alias
    public function lock()
    {
        return $this->belongsTo(SmartLock::class, 'smart_lock_id');
    }

    // smartLock alias for compatibility
    public function smartLock()
    {
        return $this->belongsTo(SmartLock::class, 'smart_lock_id');
    }

    // is_favorite
    public function getIsFavoriteAttribute()
    {
        $user = \Auth::guard('api')->check() ? \Auth::guard('api')->user() : \Auth::guard('customer')->user();
        if (! $user) {
            return false;
        }

        return $this->favorites()->where('customer_id', $user->id)->exists();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorites::class);
    }

    // top_rated
    public function getTopRatedAttribute()
    {
        //        return $this->ratings()->avg('rating');
        return true;
    }

    public function getLinkAttribute()
    {
        return route('apartments.show', $this->slug);
    }

    // ratings
    public function getTotalRatingsAttribute()
    {
        $cacheKey = "total_ratings_{$this->id}";
        $averageRating = Cache::remember($cacheKey, 120, function () {
            return $this->reviews()->avg('rating');
        });

        return $averageRating ? number_format($averageRating, 1) : '';
    }

    // reviews
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // image
    public function getImageViewAttribute()
    {
        return $this->getFirstMediaUrl('image');
    }

    // coupon_apartment

    public function coupons()
    {
        return $this->belongsToMany(Coupon::class, 'coupon_apartment', 'apartment_id', 'coupon_id');
    }

    // bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'apartment_id');
    }

    public function labels()
    {
        return $this->belongsToMany(ApartmentLabel::class, 'apartment_label_apartment', 'apartment_id', 'label_id');
    }

    public function booked_days($reservations)
    {
        $bookedDays = collect();
        $today = Carbon::today()->format('Y-m-d');

        if ($reservations && $reservations->count() > 0) {
            foreach ($reservations as $reservation) {
                // إنشاء الفترة من check_in إلى check_out (بدون تضمين check_out)
                $period = CarbonPeriod::create($reservation->check_in, $reservation->check_out->subDay());

                foreach ($period as $date) {
                    $dateString = $date->format('Y-m-d');
                    // التأكد من عدم إضافة تواريخ سابقة عن اليوم الحالي
                    if ($dateString >= $today) {
                        $bookedDays->push($dateString);
                    }
                }
            }
        }

        return $bookedDays;
    }

    public function getCalendarButton()
    {
        $url = url("admin/apartment/{$this->id}/calendar");

        return '<a href="'.$url.'" class="btn btn-sm btn-outline-primary" >
                    <i class="la la-calendar"></i> التقويم
                </a>';
    }

    public function getPricingButton()
    {
        $url = url("admin/apartment/{$this->id}/pricing");

        return '<a href="'.$url.'" class="btn btn-sm btn-outline-success" title="إدارة الأسعار">
                    <i class="la la-dollar"></i> التسعير
                </a>';
    }

    // getCopyLinkButton
    public function getCopyLinkButton()
    {
        $url = route('apartments.ics', $this->id);

        return '<a href="#" onclick="copyToClipboard(\''.$url.'\')" class="btn btn-sm btn-outline-primary">
                    <i class="la la-copy"></i> نسخ ICS  
                </a>
                <script>
                    function copyToClipboard(text) {
                        navigator.clipboard.writeText(text).then(function() {
                            alert("تم نسخ الرابط: " + text);
                        }, function() {
                            alert("فشل نسخ الرابط");
                        });
                    }
                </script>';
    }

    // category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function pricing()
    {
        return $this->hasOne(ApartmentPrice::class);
    }

    public function calendarPrices()
    {
        return $this->hasMany(ApartmentPriceCalendar::class);
    }

    public function seasonalPricings()
    {
        return $this->hasMany(SeasonalPricing::class);
    }

    public function dayOfWeekPricings()
    {
        return $this->hasMany(DayOfWeekPricing::class);
    }

    public function ownerrezMapping()
    {
        return $this->hasOne(OwnerRezPropertyMapping::class);
    }
}
