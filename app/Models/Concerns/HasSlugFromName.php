<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Renseigne la colonne slug à partir du nom, en la rendant unique : « villa », puis « villa-2 ».
 */
trait HasSlugFromName
{
    protected static function bootHasSlugFromName(): void
    {
        static::saving(function (Model $model): void {
            if ($model->slug !== null && ! $model->isDirty('name')) {
                return;
            }

            $base = Str::slug((string) $model->name) ?: 'element';
            $slug = $base;

            for ($suffix = 2; static::query()->where('slug', $slug)->whereKeyNot($model->getKey())->exists(); $suffix++) {
                $slug = "{$base}-{$suffix}";
            }

            $model->slug = $slug;
        });
    }
}
