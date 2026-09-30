<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Encaissement enregistré à la main (espèces, Mobile Money reçu à l'accueil…).
 */
class ReservationPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // « 25 000 FCFA » devient 25000
        $this->merge(['montant' => preg_replace('/\D/', '', (string) $this->input('montant'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'montant' => ['required', 'integer', 'min:100'],
            'moyen' => ['required', Rule::enum(PaymentMethod::class)],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'montant.required' => 'Indiquez le montant reçu.',
            'montant.min' => 'Le montant doit être d’au moins 100 FCFA.',
            'moyen.required' => 'Choisissez le moyen de paiement.',
        ];
    }

    public function amount(): int
    {
        return (int) $this->input('montant');
    }

    public function method(): PaymentMethod
    {
        return PaymentMethod::from((string) $this->input('moyen'));
    }

    public function reference(): ?string
    {
        $reference = trim((string) $this->input('reference'));

        return $reference === '' ? null : $reference;
    }
}
