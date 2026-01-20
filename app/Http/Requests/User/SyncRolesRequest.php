<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SyncRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        
        return $user && $user->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'role_ids.required' => 'La liste des rôles est obligatoire.',
            'role_ids.array' => 'La liste des rôles doit être un tableau.',
            'role_ids.*.exists' => 'Un ou plusieurs rôles sélectionnés n\'existent pas.',
        ];
    }
}
