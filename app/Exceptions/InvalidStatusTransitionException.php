<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class InvalidStatusTransitionException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage() ?: 'Transition de statut invalide.',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
