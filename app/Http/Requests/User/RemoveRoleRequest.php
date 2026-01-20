<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class RemoveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => 'required|exists:roles,id',
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'L\'identifiant du rôle est obligatoire.',
            'role_id.exists' => 'Le rôle sélectionné n\'existe pas.',
        ];
    }
}
