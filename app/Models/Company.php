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
    ];

    protected $casts = [
        'contact' => 'array',
        'bluewave_status' => 'boolean',
        'current_score' => 'integer',
    ];

    public const REQUIRED_DOC_TYPES = [
        'KKF Uittreksel',
        'Vergunning',
        'Belastingverklaring',
        'ID Bewijs',
    ];
}
