<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'category_selected',
        'detected_type',
        'status',
        'extracted_data',
        'expiry_date',
        'ai_feedback',
        'source_file_url',
        'file_hash_sha256',
        'mime_type',
        'original_filename',
        'file_size',
        'ocr_confidence',
        'ai_confidence',
        'summary_file_path',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'file_size' => 'integer',
        'ocr_confidence' => 'float',
        'ai_confidence' => 'float',
        'expiry_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
