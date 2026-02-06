<?php

declare(strict_types=1);

namespace HybridGram\Http\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
