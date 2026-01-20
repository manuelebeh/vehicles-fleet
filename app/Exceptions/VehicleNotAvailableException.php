<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class VehicleNotAvailableException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Le véhicule n\'est pas disponible.',
        ], Response::HTTP_CONFLICT);
    }
}
