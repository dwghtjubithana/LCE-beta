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

    private const ENCRYPTED_KEYS = [
        'gemini_api_key',
        'email_smtp_password',
        'auth_google_client_secret',
        'auth_microsoft_client_secret',
    ];

    public static function getValue(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if (!$row) {
            return $default;
        }
        $value = $row->value;
        if (in_array($key, self::ENCRYPTED_KEYS, true) && $value) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($value);
            } catch (\Throwable $e) {
                return $value;
            }
        }
        return $value;
    }

    public static function setValue(string $key, $value): void
    {
        $normalized = $value;
        if (is_bool($normalized)) {
            $normalized = $normalized ? '1' : '0';
        }
        if (in_array($key, self::ENCRYPTED_KEYS, true) && $normalized !== null && $normalized !== '') {
            $normalized = \Illuminate\Support\Facades\Crypt::encryptString((string) $normalized);
        }

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $normalized]
        );
    }

    public static function hasRawValue(string $key): bool
    {
        $row = static::where('key', $key)->first();
        if (!$row) {
            return false;
        }

        $value = $row->value;
        return $value !== null && trim((string) $value) !== '';
    }
}
