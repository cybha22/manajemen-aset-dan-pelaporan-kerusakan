<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

class ValidateCsrfForAll extends ValidateCsrfToken
{
    protected $except = [
        'api/telegram/webhook',
    ];

    protected function isReading($request): bool
    {
        return false;
    }
}
