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
        'is_baseline',
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
        'is_baseline' => 'boolean',
    ];

    public const BASELINE_DOC_TYPES = [
        'KKF Uittreksel',
        'CRIB',
        'UBO',
    ];

    public const GATE2_DOC_TYPES = [
        'HSE',
        'ISO',
        'IOGP',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function files()
    {
        return $this->hasMany(DocumentFile::class);
    }
}
