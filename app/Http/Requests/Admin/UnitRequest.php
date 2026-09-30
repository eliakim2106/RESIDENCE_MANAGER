<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    public const MAX_IMAGES = 20;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Les prix peuvent être saisis avec des espaces ou « FCFA » : « 20 000 FCFA » devient 20000.
        $this->merge([
            'prix' => $this->digitsOrNull('prix'),
            'prix_promo' => $this->digitsOrNull('prix_promo'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_unite_id' => ['required', Rule::exists('unit_types', 'id')->where('statut', ActiveStatus::Active->value)],
            'nom' => ['required', 'string', 'max:255'],
            'nombre_unite' => ['required', 'integer', 'between:1,999'],
            'capacite' => ['required', 'integer', 'between:1,50'],
            'nombre_chambre' => ['required', 'integer', 'between:0,99'],
            'nombre_lit' => ['required', 'integer', 'between:1,99'],
            'nombre_salle_bain' => ['required', 'integer', 'between:0,99'],
            'surperficie' => ['nullable', 'integer', 'between:1,65000'],
            'prix' => ['required', 'integer', 'min:0', 'max:100000000'],
            'prix_promo' => ['nullable', 'integer', 'min:0', 'lt:prix'],
            'description' => ['nullable', 'string', 'max:10000'],
            'equipement_id' => ['nullable', 'array'],
            'equipement_id.*' => ['integer', Rule::exists('equipments', 'id')->where('statut', ActiveStatus::Active->value)],
            'images' => ['nullable', 'array', 'max:'.self::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'deleted_gallery' => ['nullable', 'json'],
            'gallery_cover' => ['nullable', 'string', 'max:255'],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $remaining = $this->unit()?->images()->whereNotIn('id', $this->deletedGalleryIds())->count() ?? 0;
                $total = $remaining + count($this->file('images', []));

                if ($total === 0) {
                    $validator->errors()->add('images', 'Ajoutez au moins une image de l’unité.');
                }

                if ($total > self::MAX_IMAGES) {
                    $validator->errors()->add('images', 'Une unité ne peut pas avoir plus de '.self::MAX_IMAGES.' images.');
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
            'type_unite_id.required' => "Le type d'unité est obligatoire.",
            'nom.required' => 'Le nom est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix_promo.lt' => 'Le prix promotionnel doit être inférieur au prix.',
            'images.*.max' => 'Chaque image doit faire 5 Mo au maximum.',
            'statut.required' => 'Le statut est obligatoire.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre_unite' => "nombre d'unités identiques",
            'capacite' => 'capacité',
            'nombre_chambre' => 'nombre de chambres',
            'nombre_lit' => 'nombre de lits',
            'nombre_salle_bain' => 'nombre de salles de bain',
            'surperficie' => 'superficie',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function unitAttributes(): array
    {
        return [
            'unit_type_id' => $this->integer('type_unite_id'),
            'name' => $this->string('nom')->trim()->toString(),
            'description' => $this->input('description'),
            'quantity' => $this->integer('nombre_unite'),
            'max_adults' => $this->integer('capacite'),
            'bedrooms' => $this->integer('nombre_chambre'),
            'beds' => $this->integer('nombre_lit'),
            'bathrooms' => $this->integer('nombre_salle_bain'),
            'size_m2' => $this->input('surperficie') ?: null,
            'base_price' => $this->integer('prix'),
            'promo_price' => $this->input('prix_promo'),
            'statut' => $this->input('statut') === 'actif' ? ActiveStatus::Active : ActiveStatus::Inactive,
        ];
    }

    /**
     * @return list<int>
     */
    public function equipmentIds(): array
    {
        return array_map('intval', $this->input('equipement_id', []));
    }

    /**
     * @return list<int>
     */
    public function deletedGalleryIds(): array
    {
        $ids = json_decode((string) $this->input('deleted_gallery'), true);

        return is_array($ids) ? array_values(array_map('intval', array_filter($ids, 'is_numeric'))) : [];
    }

    private function unit(): ?Unit
    {
        $unit = $this->route('unite');

        return $unit instanceof Unit ? $unit : null;
    }

    private function digitsOrNull(string $key): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->input($key));

        return $digits === '' ? null : $digits;
    }
}
