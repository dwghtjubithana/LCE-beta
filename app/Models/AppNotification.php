<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'company_id',
        'document_id',
        'type',
        'channel',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->whereNull('sent_at');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
