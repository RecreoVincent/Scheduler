<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Keep paginated portal tables consistent and only accept safe page sizes.
     */
    protected function perPage(Request $request): int
    {
        $perPage = filter_var($request->query('per_page', 10), FILTER_VALIDATE_INT);

        return in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 10;
    }
}
