<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiCsrfOrToken
{
    protected $except = [
        'sanctum/csrf-cookie',
        'api/telegram/webhook',
        'api/auth/login',
        'api/tickets',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return $next($request);
            }
        }

        if ($request->bearerToken()) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $xsrf = $request->header('X-XSRF-TOKEN');

        if (!$xsrf) {
            return response()->json(['message' => 'CSRF token mismatch.'], 419);
        }

        return $next($request);
    }
}
