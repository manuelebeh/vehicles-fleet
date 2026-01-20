<?php

namespace App\Http\Requests\Vehicle;

use App\Enums\VehicleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        
        return $user && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(VehicleStatus::all()),
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
