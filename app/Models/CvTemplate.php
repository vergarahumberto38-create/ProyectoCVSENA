<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CvTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_ats_friendly',
        'description',
    ];

    protected $casts = [
        'is_ats_friendly' => 'boolean',
    ];

    public function exports(): HasMany
    {
        return $this->hasMany(CvExport::class);
    }
}
