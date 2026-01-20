<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait HandlesPagination
{
    /**
     * Get the per page value from the request, with validation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $default
     * @param  int  $max
     * @return int
     */
    protected function getPerPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = (int) $request->get('per_page', $default);
        
        // Ensure per_page is between 1 and max
        return min(max($perPage, 1), $max);
    }
}
