<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Enregistre les images envoyées par les formulaires (logo, galerie d'établissement ou d'unité)
 * sur le disque public, et tient à jour l'image principale.
 */
class GalleryManager
{
    /**
     * Applique les suppressions, ajoute les nouveaux fichiers et désigne l'image principale.
     *
     * L'image principale est identifiée par l'id d'une image existante ou par le nom d'origine d'un nouveau fichier.
     * Avec une colonne is_cover (établissements), elle y est marquée ; sinon (unités), elle passe en première position.
     *
     * @param  Closure(): HasMany<covariant Model, covariant Model>  $images
     * @param  list<int>  $deletedIds
     * @param  array<int, UploadedFile>  $files
     */
    public function sync(Closure $images, string $directory, array $deletedIds, array $files, ?string $cover, bool $hasCoverColumn): void
    {
        foreach ($images()->whereIn('id', $deletedIds)->get() as $image) {
            $this->deleteFile($image->path);
            $image->delete();
        }

        $position = (int) $images()->max('position') + 1;
        $newCover = null;

        foreach ($files as $file) {
            $image = $images()->create([
                'path' => $file->store($directory, 'public'),
                'position' => $position++,
            ]);

            if ($newCover === null && $cover !== null && $file->getClientOriginalName() === $cover) {
                $newCover = $image;
            }
        }

        $gallery = $images()->orderBy('position')->orderBy('id')->get();

        if ($gallery->isEmpty()) {
            return;
        }

        $coverImage = (is_numeric($cover) ? $gallery->firstWhere('id', (int) $cover) : null)
            ?? $newCover
            ?? ($hasCoverColumn ? $gallery->firstWhere('is_cover', true) : null)
            ?? $gallery->first();

        if ($hasCoverColumn) {
            $images()->whereKeyNot($coverImage->getKey())->update(['is_cover' => false]);
            $coverImage->update(['is_cover' => true]);

            return;
        }

        $gallery->reject(fn (Model $image): bool => $image->is($coverImage))
            ->prepend($coverImage)
            ->values()
            ->each(fn (Model $image, int $index) => $image->update(['position' => $index]));
    }

    /**
     * Remplace un fichier unique (logo) : retourne le nouveau chemin, null s'il est retiré, l'ancien sinon.
     */
    public function replace(?string $currentPath, ?UploadedFile $file, bool $remove, string $directory): ?string
    {
        if ($file !== null) {
            $this->deleteFile($currentPath);

            return $file->store($directory, 'public');
        }

        if ($remove) {
            $this->deleteFile($currentPath);

            return null;
        }

        return $currentPath;
    }

    public function deleteFile(?string $path): void
    {
        if ($path !== null && $path !== '' && ! Str::startsWith($path, ['http://', 'https://'])) {
            Storage::disk('public')->delete($path);
        }
    }

    public function deleteDirectory(string $directory): void
    {
        Storage::disk('public')->deleteDirectory($directory);
    }
}
