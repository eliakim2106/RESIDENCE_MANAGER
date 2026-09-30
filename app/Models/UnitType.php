<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Models\Concerns\HasFontAwesomeIcon;
use App\Models\Concerns\HasSlugFromName;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class UnitType extends Model
{
    use HasFactory, HasFontAwesomeIcon, HasSlugFromName;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
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

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
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
