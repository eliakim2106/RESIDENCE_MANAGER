<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Paramètre de la plateforme modifiable depuis le back-office (clé / valeur).
 * Garde la date et l'auteur de sa dernière modification.
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
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Compte qui a modifié ce réglage en dernier.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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
     * Écrit un paramètre. Une valeur inchangée n'est pas réenregistrée : date et auteur de modification restent exacts.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $setting = static::query()->firstOrNew(['key' => $key]);
        $setting->fill(['value' => $value, 'group' => $group]);

        if ($setting->isDirty()) {
            $setting->updated_by = Auth::id();
            $setting->save();
        }
    }
}
