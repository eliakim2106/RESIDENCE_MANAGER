<?php

namespace App\Models;

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
        'is_active',
    ];

    #[Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
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
        $query->where('is_active', true);
    }
}
