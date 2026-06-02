<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    // Parameter ini menjaga endpoint lama tetap array kecuali client meminta paginator.
    protected function wantsPagination(Request $request): bool
    {
        return $request->boolean('paginated') || $request->boolean('paginate');
    }

    // Batas maksimum mencegah client meminta data terlalu besar dalam satu halaman.
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return max(1, min($perPage, $max));
    }
}
