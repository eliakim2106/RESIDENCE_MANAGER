<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Modification du contenu des pages (Paramètres du site > Contenu des pages), à partir du schéma de config/site-content.php :
 * règles de validation, enregistrement des images envoyées, suppression des images remplacées.
 *
 * Formulaire attendu, par bloc : blocks[bloc][champ], blocks[bloc][visible] ;
 * images : blocks[bloc][champ] (image actuelle) et blocks[bloc][champ_file] (nouvelle image) ;
 * listes : blocks[bloc][items][n][champ], dans l'ordre d'affichage.
 */
class SiteContentEditor
{
    /** Dossier des images envoyées, sur le disque public */
    public const IMAGE_DIRECTORY = 'site';

    /** Chemins d'image acceptés : fichiers du site ou images envoyées */
    private const IMAGE_PATH = '/^(assets|site)\/[A-Za-z0-9\/_.-]+$/';

    /** Liens acceptés : chemin du site, ancre, adresse complète, email, téléphone */
    private const LINK = '/^(\/|#|https?:\/\/|mailto:|tel:)\S*$/';

    public function __construct(private SiteSettings $site) {}

    /**
     * @return array<string, array<string, mixed>> page => définition
     */
    public static function pages(): array
    {
        return config('site-content.pages');
    }

    /**
     * @return array<string, array<string, mixed>> bloc => définition, pour une page
     */
    public function blocks(string $page): array
    {
        return collect(self::pages()[$page]['blocks'])
            ->mapWithKeys(fn (string $block): array => [$block => config("site-content.blocks.{$block}")])
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, mixed>
     */
    public function rules(string $page): array
    {
        $rules = [];

        foreach ($this->blocks($page) as $block => $definition) {
            $prefix = "blocks.{$block}";

            if ($definition['visible'] ?? false) {
                $rules["{$prefix}.visible"] = ['nullable', 'boolean'];
            }

            foreach ($definition['fields'] ?? [] as $field => $spec) {
                $rules += $this->fieldRules("{$prefix}.{$field}", $spec);
            }

            if ($list = $definition['list'] ?? null) {
                $rules["{$prefix}.items"] = ['required', 'array', "min:{$list['min']}", "max:{$list['max']}"];

                foreach ($list['fields'] as $field => $spec) {
                    $rules += $this->fieldRules("{$prefix}.items.*.{$field}", $spec);
                }
            }
        }

        return $rules;
    }

    /**
     * Noms des champs dans les messages d'erreur : « Titre (Diaporama d’accueil, diapositive 2) ».
     *
     * @return array<string, string>
     */
    public function attributes(Request $request, string $page): array
    {
        $attributes = [];

        foreach ($this->blocks($page) as $block => $definition) {
            foreach ($definition['fields'] ?? [] as $field => $spec) {
                $attributes["blocks.{$block}.{$field}"] = mb_strtolower($spec['label']).' ('.$definition['label'].')';
                $attributes["blocks.{$block}.{$field}_file"] = mb_strtolower($spec['label']).' ('.$definition['label'].')';
            }

            if ($list = $definition['list'] ?? null) {
                $attributes["blocks.{$block}.items"] = mb_strtolower($list['label']).'s ('.$definition['label'].')';
                $position = 0;

                foreach (array_keys((array) $request->input("blocks.{$block}.items", [])) as $key) {
                    $position++;

                    foreach ($list['fields'] as $field => $spec) {
                        $label = mb_strtolower($spec['label']).' ('.mb_strtolower($list['label']).' '.$position.')';
                        $attributes["blocks.{$block}.items.{$key}.{$field}"] = $label;
                        $attributes["blocks.{$block}.items.{$key}.{$field}_file"] = $label;
                    }
                }
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, list<mixed>>
     */
    private function fieldRules(string $key, array $spec): array
    {
        $presence = ($spec['required'] ?? false) ? 'required' : 'nullable';

        return match ($spec['type']) {
            'image' => [
                $key => ['nullable', 'string', 'max:255', 'regex:'.self::IMAGE_PATH, 'not_regex:/\.\./'],
                "{$key}_file" => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            'link' => [$key => [$presence, 'string', 'max:'.($spec['max'] ?? 255), 'regex:'.self::LINK]],
            'icon' => [$key => [$presence, Rule::in(array_keys(config('site-content.icons')))]],
            default => [$key => [$presence, 'string', 'max:'.($spec['max'] ?? 255)]],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | ENREGISTREMENT
    |--------------------------------------------------------------------------
    */

    /**
     * Enregistre tous les blocs d'une page (données déjà validées).
     */
    public function save(Request $request, string $page): void
    {
        foreach ($this->blocks($page) as $block => $definition) {
            $input = (array) $request->input("blocks.{$block}", []);
            $value = [];

            if ($definition['visible'] ?? false) {
                $value['visible'] = (bool) ($input['visible'] ?? false);
            }

            foreach ($definition['fields'] ?? [] as $field => $spec) {
                $value[$field] = $this->value($request, "blocks.{$block}.{$field}", $spec, $input[$field] ?? null);
            }

            if ($list = $definition['list'] ?? null) {
                $value['items'] = [];

                // Ordre du formulaire = ordre d'affichage
                foreach ((array) ($input['items'] ?? []) as $key => $item) {
                    $value['items'][] = collect($list['fields'])
                        ->map(fn (array $spec, string $field) => $this->value($request, "blocks.{$block}.items.{$key}.{$field}", $spec, $item[$field] ?? null))
                        ->all();
                }
            }

            $before = $this->uploadedImages($this->site->content($block));
            $this->site->saveContent($block, $value);
            $this->deleteImages(array_diff($before, $this->uploadedImages($value)));
        }
    }

    /**
     * Rétablit le contenu d'origine d'un bloc ; ses images envoyées sont supprimées.
     */
    public function reset(string $block): void
    {
        $images = $this->uploadedImages($this->site->content($block));
        $this->site->saveContent($block, null);
        $this->deleteImages($images);
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function value(Request $request, string $key, array $spec, mixed $input): string
    {
        if ($spec['type'] === 'image') {
            $file = $request->file("{$key}_file");

            return $file instanceof UploadedFile
                ? $file->store(self::IMAGE_DIRECTORY, 'public')
                : (string) $input;
        }

        return trim((string) $input);
    }

    /**
     * Images envoyées (dossier site/) utilisées par un contenu de bloc.
     *
     * @param  array<string, mixed>  $content
     * @return list<string>
     */
    private function uploadedImages(array $content): array
    {
        $paths = [];

        array_walk_recursive($content, function (mixed $value) use (&$paths): void {
            if (is_string($value) && str_starts_with($value, self::IMAGE_DIRECTORY.'/')) {
                $paths[] = $value;
            }
        });

        return array_values(array_unique($paths));
    }

    /**
     * @param  array<int, string>  $paths
     */
    private function deleteImages(array $paths): void
    {
        if ($paths !== []) {
            Storage::disk('public')->delete(array_values($paths));
        }
    }
}
