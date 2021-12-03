<?php

namespace Secomapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestHasChargeId
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->has('charge_id')) {
            Log::warning('request missing application charge id');

            return response()->message('request missing application charge id');
        }

        return $next($request);
    }
}