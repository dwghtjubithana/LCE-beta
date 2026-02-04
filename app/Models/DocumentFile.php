<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'side',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size',
        'file_hash_sha256',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}

