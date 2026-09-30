<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'region',
        'country',
        'image_path',
        'statut',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'statut' => ActiveStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('statut', ActiveStatus::Active);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->statut === ActiveStatus::Active;
    }
}
