<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $table = 'email_logs';

    protected $fillable = [
        'template_key',
        'to_email',
        'subject',
        'status',
        'error_message',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
