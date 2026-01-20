<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        
        return $user && $user->hasRole('admin');
    }

    public function rules(): array
    {
        $roleId = $this->route('role')->id ?? null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $nameRules = $isUpdate 
            ? ['sometimes', 'required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($roleId)]
            : ['required', 'string', 'max:50', 'unique:roles,name'];

        return [
            'name' => $nameRules,
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
