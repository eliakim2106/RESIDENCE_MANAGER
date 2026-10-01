<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Numéro de téléphone valable pour l'indicatif choisi dans le même formulaire (champ indicatif_telephone).
 * Le numéro doit déjà être normalisé (PhoneNumber::normalize, dans prepareForValidation).
 */
class PhoneNumberRule implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(private readonly string $dialField = 'indicatif_telephone') {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $dial = (string) ($this->data[$this->dialField] ?? '');
        $country = PhoneNumber::country($dial);

        if ($country === null) {
            return; // L'indicatif inconnu est signalé par sa propre règle
        }

        if (! PhoneNumber::isValid($dial, (string) $value)) {
            $lengths = implode(' ou ', $country['lengths']);

            $fail("Un numéro {$country['name']} ({$dial}) compte {$lengths} chiffres, par exemple {$country['example']}.");
        }
    }
}
