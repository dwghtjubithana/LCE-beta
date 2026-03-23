<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'project',
        'date',
        'submission_deadline',
        'client',
        'location',
        'sector',
        'reference_code',
        'contract_type',
        'budget_label',
        'eligibility',
        'details_url',
        'source_name',
        'source_url',
        'cover_image_url',
        'issuer_logo_url',
        'attachments',
        'description',
        'is_direct_work',
        'status',
        'submitted_by_user_id',
        'submitted_at',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'date' => 'date',
        'submission_deadline' => 'date',
        'attachments' => 'array',
        'is_direct_work' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
