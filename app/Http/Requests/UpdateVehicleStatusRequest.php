<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(['available', 'maintenance', 'out_of_service']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est obligatoire.',
            'status.in' => 'Le statut doit être : available, maintenance ou out_of_service.',
        ];
    }
}
