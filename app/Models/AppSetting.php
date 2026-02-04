<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if (!$row) {
            return $default;
        }
        $value = $row->value;
        if ($key === 'gemini_api_key' && $value) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($value);
            } catch (\Throwable $e) {
                return $value;
            }
        }
        return $value;
    }
}
