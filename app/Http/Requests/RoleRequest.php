<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role')->id ?? null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [
                $isUpdate ? 'sometimes|required' : 'required',
                'string',
                'max:50',
                $isUpdate 
                    ? Rule::unique('roles', 'name')->ignore($roleId)
                    : 'unique:roles,name',
            ],
            'display_name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'name.unique' => 'Ce nom de rôle existe déjà.',
            'name.max' => 'Le nom du rôle ne peut pas dépasser 50 caractères.',
            'display_name.max' => 'Le nom d\'affichage ne peut pas dépasser 100 caractères.',
        ];
    }
}
