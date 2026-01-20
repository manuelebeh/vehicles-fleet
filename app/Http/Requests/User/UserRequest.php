<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $targetUser = $this->route('user');
        
        // Seul un admin peut créer/modifier des utilisateurs
        // ou un utilisateur peut modifier son propre profil
        if (!$user) {
            return false;
        }
        
        if ($this->isMethod('POST')) {
            // Seul un admin peut créer des utilisateurs
            return $user->hasRole('admin');
        }
        
        if ($targetUser) {
            // Un admin peut modifier n'importe quel utilisateur
            // ou un utilisateur peut modifier son propre profil
            return $user->hasRole('admin') || $user->id === $targetUser->id;
        }
        
        return false;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'email' => [
                $isUpdate ? 'sometimes' : 'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'min:8',
            ],
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'first_name.max' => 'Le prénom ne peut pas dépasser 100 caractères.',
            'last_name.max' => 'Le nom ne peut pas dépasser 100 caractères.',
        ];
    }
}
