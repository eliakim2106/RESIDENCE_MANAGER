<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Informations du compte connecté (page « Mon profil »).
 */
class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'telephone' => ['nullable', 'string', 'max:30'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['nullable', 'string', 'max:100'],
            'entreprise' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'supprimer_photo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Indiquez votre nom.',
            'email.unique' => 'Cette adresse email est déjà utilisée par un autre compte.',
            'photo.image' => 'La photo doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WebP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $attributes = [
            'name' => $this->string('nom')->trim()->toString(),
            'email' => $this->string('email')->trim()->toString(),
            'phone' => $this->input('telephone'),
            'city' => $this->input('ville'),
            'country' => $this->input('pays'),
        ];

        // Nom de l'entreprise : utile aux propriétaires uniquement
        if ($this->user()->isOwner()) {
            $attributes['company_name'] = $this->input('entreprise');
        }

        return $attributes;
    }
}
