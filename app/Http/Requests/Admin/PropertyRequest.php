<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use App\Models\Property;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Formulaire d'établissement en 6 étapes : informations, localisation, contact & accueil, médias, publication, SEO.
 */
class PropertyRequest extends FormRequest
{
    public const MAX_GALLERY_IMAGES = 20;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'telephone' => preg_replace('/\D/', '', (string) $this->input('telephone')),
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('nom'))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Informations
            'type_etablissement_id' => ['required', Rule::exists('property_types', 'id')->where('statut', ActiveStatus::Active->value)],
            'nom' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],

            // Localisation
            'city_id' => ['required', Rule::exists('cities', 'id')->where('statut', ActiveStatus::Active->value)],
            'commune' => ['required', 'string', 'max:100'],
            'quartier' => ['nullable', 'string', 'max:100'],
            'adresse' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // Contact & accueil
            'telephone' => ['required', 'digits:10'],
            'email' => ['nullable', 'email', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'etoile' => ['nullable', 'integer', 'between:0,5'],
            'gestion_unites' => ['nullable', 'boolean'],

            // Médias
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'deleted_logo' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array', 'max:'.self::MAX_GALLERY_IMAGES],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'deleted_gallery' => ['nullable', 'json'],
            'gallery_cover' => ['nullable', 'string', 'max:255'],

            // Publication
            'statut' => ['required', Rule::in(['actif', 'inactif'])],

            // SEO
            'meta_title' => ['required', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('properties', 'slug')->ignore($this->property())],
        ];
    }

    /**
     * La galerie doit contenir au moins une image une fois les suppressions et ajouts appliqués.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $remaining = $this->property()?->images()->whereNotIn('id', $this->deletedGalleryIds())->count() ?? 0;
                $total = $remaining + count($this->file('gallery', []));

                if ($total === 0) {
                    $validator->errors()->add('gallery', 'La galerie est obligatoire : ajoutez au moins une image.');
                }

                if ($total > self::MAX_GALLERY_IMAGES) {
                    $validator->errors()->add('gallery', 'La galerie ne peut pas dépasser '.self::MAX_GALLERY_IMAGES.' images.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type_etablissement_id.required' => "Le type d'établissement est obligatoire.",
            'nom.required' => "Le nom de l'établissement est obligatoire.",
            'nom.min' => "Le nom de l'établissement doit contenir au moins 3 caractères.",
            'city_id.required' => 'La ville est obligatoire.',
            'commune.required' => 'La commune est obligatoire.',
            'adresse.required' => "L'adresse est obligatoire.",
            'telephone.required' => 'Le téléphone est obligatoire.',
            'telephone.digits' => 'Le téléphone doit contenir 10 chiffres.',
            'logo.max' => 'Le logo dépasse 2 Mo.',
            'gallery.*.max' => 'Chaque image de la galerie doit faire 5 Mo au maximum.',
            'statut.required' => 'Le statut de publication est obligatoire.',
            'meta_title.required' => 'Le titre méta est obligatoire.',
            'slug.unique' => 'Cette URL est déjà utilisée par un autre établissement.',
        ];
    }

    /**
     * Colonnes de l'établissement (hors médias).
     *
     * @return array<string, mixed>
     */
    public function propertyAttributes(): array
    {
        return [
            'property_type_id' => $this->integer('type_etablissement_id'),
            'name' => $this->string('nom')->trim()->toString(),
            'slug' => $this->input('slug'),
            'description' => $this->input('description'),
            'city_id' => $this->integer('city_id'),
            'district' => $this->string('commune')->trim()->toString(),
            'neighborhood' => $this->input('quartier'),
            'address' => $this->string('adresse')->trim()->toString(),
            'latitude' => $this->input('latitude'),
            'longitude' => $this->input('longitude'),
            'phone' => $this->input('telephone'),
            'email' => $this->input('email'),
            'website' => $this->input('site_web'),
            'check_in_from' => $this->input('check_in') ?: '14:00',
            'check_out_until' => $this->input('check_out') ?: '12:00',
            'star_rating' => $this->integer('etoile') ?: null,
            'manages_units' => $this->boolean('gestion_unites'),
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
        ];
    }

    /**
     * « En ligne » : publié pour un administrateur, soumis à validation pour un propriétaire (PropertyModeration).
     */
    public function wantsOnline(): bool
    {
        return $this->input('statut') === 'actif';
    }

    /**
     * Identifiants des images existantes retirées par l'utilisateur.
     *
     * @return list<int>
     */
    public function deletedGalleryIds(): array
    {
        $ids = json_decode((string) $this->input('deleted_gallery'), true);

        return is_array($ids) ? array_values(array_map('intval', array_filter($ids, 'is_numeric'))) : [];
    }

    private function property(): ?Property
    {
        $property = $this->route('etablissement');

        return $property instanceof Property ? $property : null;
    }
}
