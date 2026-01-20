<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class ReservationConflictException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Le véhicule est déjà réservé pour cette période.',
        ], Response::HTTP_CONFLICT);
    }
}
