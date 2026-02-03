<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'sector_applicability',
        'required_keywords',
        'max_age_months',
        'constraints',
    ];

    protected $casts = [
        'sector_applicability' => 'array',
        'required_keywords' => 'array',
        'constraints' => 'array',
    ];
}
