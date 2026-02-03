<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'owner_user_id',
        'company_name',
        'sector',
        'experience',
        'contact',
        'bluewave_status',
        'current_score',
        'verification_level',
        'public_slug',
        'display_name',
        'profile_photo_path',
        'address',
        'lat',
        'lng',
        'verification_status',
    ];

    protected $casts = [
        'contact' => 'array',
        'bluewave_status' => 'boolean',
        'current_score' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public const REQUIRED_DOC_TYPES = [
        'KKF Uittreksel',
        'Vergunning',
        'Belastingverklaring',
        'ID Bewijs',
    ];
}
