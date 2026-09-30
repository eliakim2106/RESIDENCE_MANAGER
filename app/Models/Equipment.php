<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\EquipmentCategory;
use App\Models\Concerns\HasFontAwesomeIcon;
use App\Models\Concerns\HasSlugFromName;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

class Equipment extends Model
{
    use HasFactory, HasFontAwesomeIcon, HasSlugFromName;

    protected $table = 'equipments';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'category',
        'is_popular',
        'statut',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'category' => EquipmentCategory::class,
            'is_popular' => 'boolean',
            'statut' => ActiveStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class);
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
