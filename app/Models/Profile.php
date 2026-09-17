<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'address',
        'city',
        'country',
        'linkedin_url',
        'portfolio_url',
        'headline',
        'summary',
        'photo_path',
        'photo_ats_path',
        'photo_ai_status',
        'photo_ai_error',
        'public_slug',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Profile $profile) {
            if (empty($profile->public_slug)) {
                $profile->public_slug = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)->orderBy('order');
    }

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class)->orderBy('order');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class)->orderBy('order');
    }

    public function languages(): HasMany
    {
        return $this->hasMany(Language::class)->orderBy('order');
    }

    public function exports(): HasMany
    {
        return $this->hasMany(CvExport::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('cv.public', $this->public_slug);
    }
}
