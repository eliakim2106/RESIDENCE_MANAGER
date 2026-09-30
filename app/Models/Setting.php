<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Paramètre de la plateforme modifiable depuis le back-office (clé / valeur).
 */
class Setting extends Model
{
    private const CACHE_KEY = 'settings.all';

    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /*
    |--------------------------------------------------------------------------
    | LECTURE ET ÉCRITURE
    |--------------------------------------------------------------------------
    */

    /**
     * Lit un paramètre (mis en cache).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever(self::CACHE_KEY, fn (): array => static::pluck('value', 'key')->all());

        return $settings[$key] ?? $default;
    }

    /**
     * Écrit un paramètre.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }
}
