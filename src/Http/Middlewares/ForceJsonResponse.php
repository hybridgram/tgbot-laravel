<?php

namespace HybridGram\Http\Middlewares;

use Closure;

final class ForceJsonResponse
{
    public function handle($request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        return $next($request);
    }
}