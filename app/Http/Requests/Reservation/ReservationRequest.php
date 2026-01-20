<?php

namespace App\Http\Requests\Reservation;

use App\Enums\ReservationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $reservation = $this->route('reservation');
        
        if (!$user) {
            return false;
        }
        
        if ($user->hasRole('admin')) {
            return true;
        }
        
        if ($this->isMethod('POST')) {
            // Pour les utilisateurs non-admin, permettre la création si :
            // 1. Pas de user_id fourni (sera forcé par le contrôleur) OU
            // 2. user_id fourni correspond à l'utilisateur connecté
            $requestedUserId = $this->input('user_id');
            return !$requestedUserId || (int) $requestedUserId === $user->id;
        }
        
        if ($reservation) {
            return $reservation->user_id === $user->id;
        }
        
        return false;
    }

    public function rules(): array
    {
        $reservationId = $this->route('reservation')->id ?? null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'vehicle_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'exists:vehicles,id',
            ],
            'start_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
                'after_or_equal:now',
            ],
            'end_date' => [
                $isUpdate ? 'sometimes' : 'required',
                'date',
                'after:start_date',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(ReservationStatus::all()),
            ],
            'purpose' => 'nullable|string',
        ];

        // user_id est requis pour les admins, nullable pour les clients (sera forcé par le contrôleur)
        if ($isUpdate) {
            $rules['user_id'] = ['sometimes', 'exists:users,id'];
        } else {
            $user = $this->user();
            if ($user && $user->hasRole('admin')) {
                $rules['user_id'] = ['required', 'exists:users,id'];
            } else {
                // Pour les clients, user_id est nullable et sera ajouté par le contrôleur
                $rules['user_id'] = ['nullable'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'L\'utilisateur est obligatoire.',
            'user_id.exists' => 'L\'utilisateur sélectionné n\'existe pas.',
            'vehicle_id.required' => 'Le véhicule est obligatoire.',
            'vehicle_id.exists' => 'Le véhicule sélectionné n\'existe pas.',
            'start_date.required' => 'La date de début est obligatoire.',
            'start_date.date' => 'La date de début doit être une date valide.',
            'start_date.after_or_equal' => 'La date de début doit être supérieure ou égale à maintenant.',
            'end_date.required' => 'La date de fin est obligatoire.',
            'end_date.date' => 'La date de fin doit être une date valide.',
            'end_date.after' => 'La date de fin doit être postérieure à la date de début.',
            'status.in' => 'Le statut doit être : ' . implode(', ', ReservationStatus::all()) . '.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('start_date') && $this->has('end_date')) {
                $startDate = \Carbon\Carbon::parse($this->start_date);
                $endDate = \Carbon\Carbon::parse($this->end_date);

                if (abs($endDate->diffInMinutes($startDate)) < 60) {
                    $validator->errors()->add(
                        'end_date',
                        'La durée de la réservation doit être d\'au moins 1 heure.'
                    );
                }
            }
        });
    }
}
