<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReservationCancelRequest extends FormRequest
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
            'motif' => ['nullable', 'string', 'max:500'],
            'rembourser' => ['nullable', 'boolean'],
        ];
    }

    public function reason(): ?string
    {
        $reason = trim((string) $this->input('motif'));

        return $reason === '' ? null : $reason;
    }
}
