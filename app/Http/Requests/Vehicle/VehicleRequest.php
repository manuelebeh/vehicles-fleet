<?php

namespace App\Http\Requests\Vehicle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicleId = $this->route('vehicle')->id ?? null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $currentYear = (int) date('Y');

        return [
            'brand' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
            ],
            'model' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
            ],
            'license_plate' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:30',
                Rule::unique('vehicles', 'license_plate')->ignore($vehicleId),
            ],
            'year' => [
                'nullable',
                'integer',
                'min:1900',
                'max:' . ($currentYear + 1),
            ],
            'color' => 'nullable|string|max:50',
            'status' => [
                'nullable',
                'string',
                Rule::in(['available', 'maintenance', 'out_of_service']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'brand.required' => 'La marque est obligatoire.',
            'brand.max' => 'La marque ne peut pas dépasser 100 caractères.',
            'model.required' => 'Le modèle est obligatoire.',
            'model.max' => 'Le modèle ne peut pas dépasser 100 caractères.',
            'license_plate.required' => 'La plaque d\'immatriculation est obligatoire.',
            'license_plate.unique' => 'Cette plaque d\'immatriculation existe déjà.',
            'license_plate.max' => 'La plaque d\'immatriculation ne peut pas dépasser 30 caractères.',
            'year.min' => 'L\'année doit être supérieure ou égale à 1900.',
            'year.max' => 'L\'année ne peut pas être supérieure à l\'année en cours + 1.',
            'color.max' => 'La couleur ne peut pas dépasser 50 caractères.',
            'status.in' => 'Le statut doit être : available, maintenance ou out_of_service.',
        ];
    }
}
