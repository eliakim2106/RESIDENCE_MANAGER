<?php

namespace App\Http\Requests\Admin;

use App\Enums\EquipmentCategory;
use App\Models\Equipment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentRequest extends FormRequest
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
        /** @var Equipment|null $equipment */
        $equipment = $this->route('equipement');

        return [
            'nom' => ['required', 'string', 'max:100', Rule::unique('equipments', 'name')->ignore($equipment)],
            'icon' => ['required', 'string', 'max:100', 'regex:/^fa-[a-z0-9-]+$/'],
            'category' => ['required', Rule::enum(EquipmentCategory::class)],
            'is_popular' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['actif', 'inactif'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'nom.unique' => 'Cet équipement existe déjà.',
            'icon.required' => "L'icône est obligatoire.",
            'icon.regex' => "L'icône doit être un nom Font Awesome, ex. fa-wifi.",
            'category.required' => 'La catégorie est obligatoire.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function equipmentAttributes(): array
    {
        return [
            'name' => $this->string('nom')->trim()->toString(),
            'icon' => $this->string('icon')->trim()->toString(),
            'category' => $this->input('category'),
            'is_popular' => $this->boolean('is_popular'),
            'is_active' => $this->input('status') === 'actif',
        ];
    }
}
