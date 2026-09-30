<?php

namespace App\Http\Requests\Admin;

class PropertyTypeRequest extends TypeRequest
{
    protected function table(): string
    {
        return 'property_types';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'nom.unique' => "Ce type d'établissement existe déjà.",
        ];
    }
}
