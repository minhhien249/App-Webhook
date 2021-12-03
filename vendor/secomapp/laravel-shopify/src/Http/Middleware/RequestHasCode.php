<?php

namespace Secomapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestHasCode
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->has('code')) {
            Log::warning('shopify authorization code required');

            return redirect()->route('add_app')->withMessage('shopify authorization code required');
        }

        return $next($request);
    }
}