<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formule d'abonnement : prix, limites, essai, commission et avantages affichés.
 */
class SubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // « 25 000 FCFA » devient 25000 ; un champ vide reste vide (illimité, pas de prix annuel…)
        $digits = fn (string $key) => ($value = preg_replace('/\D/', '', (string) $this->input($key))) === '' ? null : $value;

        $this->merge([
            'prix_mensuel' => $digits('prix_mensuel'),
            'prix_annuel' => $digits('prix_annuel'),
            'max_etablissements' => $digits('max_etablissements'),
            'max_unites' => $digits('max_unites'),
            'jours_essai' => $digits('jours_essai') ?? 0,
            'commission' => str_replace(',', '.', (string) ($this->input('commission') ?: 0)),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'prix_mensuel' => ['required', 'integer', 'min:0', 'max:100000000'],
            'prix_annuel' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'max_etablissements' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'max_unites' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'jours_essai' => ['required', 'integer', 'min:0', 'max:365'],
            'commission' => ['required', 'numeric', 'min:0', 'max:50'],
            'avantages' => ['nullable', 'string', 'max:2000'],
            'mise_en_avant' => ['nullable', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0', 'max:100'],
            'statut' => ['required', Rule::enum(ActiveStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Donnez un nom à la formule.',
            'prix_mensuel.required' => 'Indiquez le prix mensuel (0 pour une formule gratuite).',
            'commission.max' => 'La commission ne peut pas dépasser 50 %.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function planAttributes(): array
    {
        return [
            'name' => $this->string('nom')->trim()->toString(),
            'description' => $this->input('description'),
            'monthly_price' => (int) $this->input('prix_mensuel'),
            'yearly_price' => $this->filled('prix_annuel') ? (int) $this->input('prix_annuel') : null,
            'max_properties' => $this->filled('max_etablissements') ? (int) $this->input('max_etablissements') : null,
            'max_units' => $this->filled('max_unites') ? (int) $this->input('max_unites') : null,
            'trial_days' => (int) $this->input('jours_essai'),
            'commission_rate' => (float) $this->input('commission'),
            'features' => collect(preg_split('/\R/', (string) $this->input('avantages')))->map(fn ($line) => trim($line))->filter()->values()->all(),
            'is_featured' => $this->boolean('mise_en_avant'),
            'position' => (int) $this->input('position', 0),
            'statut' => ActiveStatus::from((string) $this->input('statut')),
        ];
    }
}
