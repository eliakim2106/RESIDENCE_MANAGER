<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActiveStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Type d'établissement ou type d'unité : nom, icône Font Awesome, description et statut.
 */
abstract class TypeRequest extends FormRequest
{
    /**
     * Table dans laquelle le nom doit être unique.
     */
    abstract protected function table(): string;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Model|null $type */
        $type = $this->route('type');

        return [
            'nom' => ['required', 'string', 'max:100', Rule::unique($this->table(), 'name')->ignore($type)],
            'icon' => ['required', 'string', 'max:100', 'regex:/^fa-[a-z0-9-]+$/'],
            'description' => ['required', 'string', 'max:1000'],
            'statut' => ['required', Rule::in(['actif', 'inactif'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'icon.required' => "L'icône est obligatoire.",
            'icon.regex' => "L'icône doit être un nom Font Awesome, ex. fa-hotel.",
            'description.required' => 'La description est obligatoire.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function typeAttributes(): array
    {
        return [
            'name' => $this->string('nom')->trim()->toString(),
            'icon' => $this->string('icon')->trim()->toString(),
            'description' => $this->string('description')->trim()->toString(),
            'statut' => ActiveStatus::from((string) $this->input('statut')),
        ];
    }
}
