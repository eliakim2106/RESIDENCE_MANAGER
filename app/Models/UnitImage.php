<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UnitImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id',
        'path',
        'caption',
        'position',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
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
