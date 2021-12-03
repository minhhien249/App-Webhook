<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Contracts\ClientApiContract;

class VerifyProxyRequest
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /** @var ClientApiContract $clientApi */
        $clientApi = app(ClientApiContract::class);
        if (!$clientApi->isValidProxyRequest($request->all())) {
            Log::warning('invalid proxy request');

            return response()->message('invalid proxy request!');
        }

        return $next($request);
    }
}