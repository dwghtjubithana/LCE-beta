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
        'client',
        'details_url',
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
        'attachments' => 'array',
        'is_direct_work' => 'boolean',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];
}
