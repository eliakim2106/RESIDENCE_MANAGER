<?php

namespace App\Http\Requests\Admin;

class UnitTypeRequest extends TypeRequest
{
    protected function table(): string
    {
        return 'unit_types';
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'nom.unique' => "Ce type d'unité existe déjà.",
        ];
    }
}
