<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use App\Enums\CancellationPolicy;
use App\Models\Property;
use App\Rules\PhoneNumberRule;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Formulaire d'établissement en 6 étapes : informations, localisation, accueil & conditions, médias, publication, SEO.
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
        $this->merge(['indicatif_telephone' => $this->input('indicatif_telephone') ?: PhoneNumber::defaultDial()]);

        $this->merge([
            'telephone' => PhoneNumber::normalize((string) $this->input('indicatif_telephone'), (string) $this->input('telephone')),
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('nom'))),
            // Les prix peuvent être saisis avec des espaces ou « FCFA » : « 20 000 FCFA » devient 20000.
            'logement_prix' => $this->digitsOrNull('logement_prix'),
            'logement_prix_promo' => $this->digitsOrNull('logement_prix_promo'),
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
            'resume' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],

            // Localisation
            'city_id' => ['required', Rule::exists('cities', 'id')->where('statut', ActiveStatus::Active->value)],
            'commune' => ['required', 'string', 'max:100'],
            'quartier' => ['nullable', 'string', 'max:100'],
            'adresse' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            // Contact & accueil
            'indicatif_telephone' => ['required', Rule::in(array_keys(config('phone.countries')))],
            'telephone' => ['required', new PhoneNumberRule],
            'email' => ['nullable', 'email', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'check_in' => ['nullable', 'date_format:H:i'],
            'arrivee_jusqua' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'etoile' => ['nullable', 'integer', 'between:0,5'],
            'gestion_unites' => ['nullable', 'boolean'],

            // Logement entier (sans gestion des unités) : décrit ici, enregistré comme l'unité unique de l'établissement
            'logement_type_id' => [Rule::requiredIf($this->isWholeHome()), 'nullable', Rule::exists('unit_types', 'id')->where('statut', ActiveStatus::Active->value)],
            'logement_capacite' => [Rule::requiredIf($this->isWholeHome()), 'nullable', 'integer', 'between:1,50'],
            'logement_chambres' => [Rule::requiredIf($this->isWholeHome()), 'nullable', 'integer', 'between:0,99'],
            'logement_lits' => [Rule::requiredIf($this->isWholeHome()), 'nullable', 'integer', 'between:1,99'],
            'logement_salles_bain' => [Rule::requiredIf($this->isWholeHome()), 'nullable', 'integer', 'between:0,99'],
            'logement_superficie' => ['nullable', 'integer', 'between:1,65000'],
            'logement_prix' => [Rule::requiredIf($this->isWholeHome()), 'nullable', 'integer', 'min:1', 'max:100000000'],
            'logement_prix_promo' => ['nullable', 'integer', 'min:1', 'lt:logement_prix'],
            'logement_equipements' => ['nullable', 'array'],
            'logement_equipements.*' => ['integer', Rule::exists('equipments', 'id')->where('statut', ActiveStatus::Active->value)],

            // Conditions de séjour (utilisées par les réservations : délai d'annulation gratuite, remboursements)
            'politique_annulation' => ['required', Rule::enum(CancellationPolicy::class)],
            'reglement' => ['nullable', 'string', 'max:3000'],
            'animaux' => ['nullable', 'boolean'],
            'fumeurs' => ['nullable', 'boolean'],
            'fetes' => ['nullable', 'boolean'],

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

                // Un logement entier n'a qu'une unité : on ne retire pas la gestion des unités à un établissement qui en a plusieurs
                $units = $this->property()?->units()->count() ?? 0;

                if ($this->isWholeHome() && $units > 1) {
                    $validator->errors()->add('gestion_unites', "Cet établissement a {$units} unités : gardez la gestion des unités, ou supprimez d’abord les unités en trop dans le menu Unités.");
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
            'arrivee_jusqua.after' => 'L’heure limite d’arrivée doit être après l’heure d’arrivée.',
            'politique_annulation.required' => 'Choisissez une politique d’annulation.',
            'resume.max' => 'Le résumé ne doit pas dépasser 500 caractères.',
            'logo.max' => 'Le logo dépasse 2 Mo.',
            'gallery.*.max' => 'Chaque image de la galerie doit faire 5 Mo au maximum.',
            'statut.required' => 'Le statut de publication est obligatoire.',
            'meta_title.required' => 'Le titre méta est obligatoire.',
            'slug.unique' => 'Cette URL est déjà utilisée par un autre établissement.',
            'logement_type_id.required' => 'Choisissez le type de logement (studio, appartement, villa…).',
            'logement_capacite.required' => 'Indiquez combien de voyageurs le logement peut accueillir.',
            'logement_chambres.required' => 'Indiquez le nombre de chambres (0 pour un studio).',
            'logement_lits.required' => 'Indiquez le nombre de lits.',
            'logement_salles_bain.required' => 'Indiquez le nombre de salles de bain.',
            'logement_prix.required' => 'Indiquez le prix d’une nuit.',
            'logement_prix_promo.lt' => 'Le prix promotionnel doit être inférieur au prix d’une nuit.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'logement_type_id' => 'type de logement',
            'logement_capacite' => 'capacité',
            'logement_chambres' => 'nombre de chambres',
            'logement_lits' => 'nombre de lits',
            'logement_salles_bain' => 'nombre de salles de bain',
            'logement_superficie' => 'superficie',
            'logement_prix' => 'prix d’une nuit',
            'logement_prix_promo' => 'prix promotionnel',
        ];
    }

    /**
     * Étape (1 à 6) qui contient chaque champ : en cas d'erreur, le formulaire rouvre la bonne étape.
     *
     * @var array<int, list<string>>
     */
    public const FIELD_STEPS = [
        1 => ['type_etablissement_id', 'nom', 'resume', 'description'],
        2 => ['city_id', 'commune', 'quartier', 'adresse', 'latitude', 'longitude'],
        3 => [
            'indicatif_telephone', 'telephone', 'email', 'site_web', 'check_in', 'arrivee_jusqua', 'check_out', 'etoile', 'gestion_unites',
            'logement_type_id', 'logement_capacite', 'logement_chambres', 'logement_lits', 'logement_salles_bain', 'logement_superficie',
            'logement_prix', 'logement_prix_promo', 'logement_equipements',
            'politique_annulation', 'reglement', 'animaux', 'fumeurs', 'fetes',
        ],
        4 => ['logo', 'deleted_logo', 'gallery', 'deleted_gallery', 'gallery_cover'],
        5 => ['statut'],
        6 => ['meta_title', 'meta_description', 'slug'],
    ];

    /**
     * Étape d'un champ (les champs de la galerie « gallery.3 » comptent pour « gallery »).
     */
    public static function stepOf(string $field): int
    {
        $field = explode('.', $field)[0];

        foreach (self::FIELD_STEPS as $step => $fields) {
            if (in_array($field, $fields, true)) {
                return $step;
            }
        }

        return 1;
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
            'short_description' => $this->filled('resume') ? $this->string('resume')->trim()->toString() : null,
            'description' => $this->input('description'),
            'city_id' => $this->integer('city_id'),
            'district' => $this->string('commune')->trim()->toString(),
            'neighborhood' => $this->input('quartier'),
            'address' => $this->string('adresse')->trim()->toString(),
            'latitude' => $this->input('latitude'),
            'longitude' => $this->input('longitude'),
            'phone' => $this->input('telephone'),
            'indicatif_telephone' => $this->input('indicatif_telephone'),
            'email' => $this->input('email'),
            'website' => $this->input('site_web'),
            'check_in_from' => $this->input('check_in') ?: '14:00',
            'check_in_until' => $this->input('arrivee_jusqua') ?: null,
            'check_out_until' => $this->input('check_out') ?: '12:00',
            'cancellation_policy' => $this->input('politique_annulation'),
            'house_rules' => $this->filled('reglement') ? $this->string('reglement')->trim()->toString() : null,
            'allows_pets' => $this->boolean('animaux'),
            'allows_smoking' => $this->boolean('fumeurs'),
            'allows_parties' => $this->boolean('fetes'),
            'star_rating' => $this->integer('etoile') ?: null,
            'manages_units' => $this->boolean('gestion_unites'),
            'meta_title' => $this->input('meta_title'),
            'meta_description' => $this->input('meta_description'),
        ];
    }

    /**
     * Logement entier : « Gestion des unités » décochée. Le logement est décrit dans ce formulaire.
     */
    public function isWholeHome(): bool
    {
        return ! $this->boolean('gestion_unites');
    }

    /**
     * Colonnes de l'unité unique d'un logement entier (WholeUnit).
     *
     * @return array{unit_type_id: int, max_adults: int, bedrooms: int, beds: int, bathrooms: int, size_m2: ?int, base_price: int, promo_price: ?int}
     */
    public function wholeUnitAttributes(): array
    {
        return [
            'unit_type_id' => $this->integer('logement_type_id'),
            'max_adults' => $this->integer('logement_capacite'),
            'bedrooms' => $this->integer('logement_chambres'),
            'beds' => $this->integer('logement_lits'),
            'bathrooms' => $this->integer('logement_salles_bain'),
            'size_m2' => $this->filled('logement_superficie') ? $this->integer('logement_superficie') : null,
            'base_price' => $this->integer('logement_prix'),
            'promo_price' => $this->filled('logement_prix_promo') ? $this->integer('logement_prix_promo') : null,
        ];
    }

    /**
     * @return list<int>
     */
    public function wholeUnitEquipmentIds(): array
    {
        return array_values(array_map('intval', (array) $this->input('logement_equipements', [])));
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

    private function digitsOrNull(string $key): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->input($key));

        return $digits === '' ? null : $digits;
    }
}
