<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'subcategory_id',
        'business_name',
        'slug',
        'bio',
        'phone',
        'whatsapp',
        'address',
        'city',
        'commune',
        'latitude',
        'longitude',
        'avatar',
        'cover_image',
        'years_experience',
        'is_verified',
        'is_active',
        'is_featured',
        'status',
        'rating_average',
        'rating_count',
        'response_time_hours',
        'subscription_plan',
        'subscription_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
            'rating_average' => 'float',
            'rating_count' => 'integer',
            'years_experience' => 'integer',
            'response_time_hours' => 'integer',
            'subscription_expires_at' => 'datetime',
        ];
    }

    // ─── Statuts possibles ───────────────────────────────────

    const STATUS_PENDING = 'pending';

    const STATUS_ACTIVE = 'active';

    const STATUS_SUSPENDED = 'suspended';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_ACTIVE,
        self::STATUS_SUSPENDED,
    ];

    // ─── Plans d'abonnement ──────────────────────────────────

    const PLAN_FREE = 'free';

    const PLAN_STARTER = 'starter';

    const PLAN_PRO = 'pro';

    const PLAN_PREMIUM = 'premium';

    const PLANS = [
        self::PLAN_FREE,
        self::PLAN_STARTER,
        self::PLAN_PRO,
        self::PLAN_PREMIUM,
    ];

    // ─── Relations ────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // ─── Accessors ────────────────────────────────────────────

    public function getHasActiveSubscriptionAttribute(): bool
    {
        return $this->subscription_plan !== self::PLAN_FREE
            && $this->subscription_expires_at
            && $this->subscription_expires_at->isFuture();
    }
}
