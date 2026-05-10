<?php

namespace App\Services\Admin;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    /**
     * Get a setting value by key, leveraging cache.
     * Falls back to config/mlm.php if not found in DB.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "system_setting:{$key}";

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            $setting = SystemSetting::where('key', $key)->first();
            
            if ($setting) {
                return $this->castValue($setting->value, $setting->type);
            }

            // Fallback to config if it exists there
            return config("mlm.{$key}", $default);
        });
    }

    public function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : (string)$value,
                'type' => $type,
                'group' => $group,
            ]
        );

        Cache::forget("system_setting:{$key}");
    }

    private function castValue(string $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'decimal' => $value, // Keep as string for bcmath
            'json' => json_decode($value, true),
            default => $value,
        };
    }
}
