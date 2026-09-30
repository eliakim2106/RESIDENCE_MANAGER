<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Inscription d'un client ou d'un propriétaire.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Indicatifs proposés par le formulaire.
     */
    public const COUNTRY_CODES = ['+225', '+221', '+223', '+226', '+228', '+229', '+233', '+224', '+33', '+32', '+1'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'telephone' => preg_replace('/\D/', '', (string) $this->input('telephone')),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'country_code' => ['required', Rule::in(self::COUNTRY_CODES)],
            'telephone' => ['required', 'digits_between:8,10'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'pays' => ['required', 'string', 'max:100'],
            'ville' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', Password::defaults()],
            'confirm_password' => ['required', 'same:password'],
            'terms' => ['accepted'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && User::withTrashed()->where('phone', $this->phone())->exists()) {
                    $validator->errors()->add('telephone', 'Ce numéro de téléphone est déjà utilisé.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom',
            'prenoms' => 'prénoms',
            'country_code' => 'indicatif',
            'telephone' => 'téléphone',
            'pays' => 'pays',
            'ville' => 'ville',
            'confirm_password' => 'confirmation du mot de passe',
            'terms' => 'conditions générales d’utilisation',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'confirm_password.same' => 'Les mots de passe ne correspondent pas.',
        ];
    }

    /**
     * Attributs du compte à créer.
     *
     * @return array<string, string>
     */
    public function userAttributes(): array
    {
        return [
            'name' => trim($this->string('prenoms').' '.$this->string('nom')),
            'email' => $this->string('email')->toString(),
            'phone' => $this->phone(),
            'country' => $this->string('pays')->trim()->toString(),
            'city' => $this->string('ville')->trim()->toString(),
            'password' => $this->string('password')->toString(),
        ];
    }

    private function phone(): string
    {
        return $this->input('country_code').' '.$this->input('telephone');
    }
}
