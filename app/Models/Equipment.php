<?php

namespace App\Models;

use App\Enums\EquipmentCategory;
use App\Models\Concerns\HasFontAwesomeIcon;
use App\Models\Concerns\HasSlugFromName;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('equipments')]
#[Fillable(['name', 'slug', 'icon', 'category', 'is_popular', 'is_active'])]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory, HasFontAwesomeIcon, HasSlugFromName;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => EquipmentCategory::class,
            'is_popular' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Property, $this>
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class);
    }

    /**
     * @return BelongsToMany<Unit, $this>
     */
    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class);
    }

    /**
     * @param  Builder<Equipment>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
