<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Override;

class PropertyImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'path',
        'caption',
        'is_cover',
        'position',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSEURS
    |--------------------------------------------------------------------------
    */

    /**
     * URL publique de l'image (fichier stocké ou URL externe).
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Str::startsWith($this->path, ['http://', 'https://'])
            ? $this->path
            : Storage::disk('public')->url($this->path));
    }
}
