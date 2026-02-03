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
    ];

    protected $casts = [
        'date' => 'date',
        'attachments' => 'array',
        'is_direct_work' => 'boolean',
    ];
}
