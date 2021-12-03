<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Contracts\ClientApiContract;

class VerifyShopifyRequest
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
        if (!$clientApi->isValidRequest($request->all())) {
            Log::warning('invalid shopify request');

            return response()->message('invalid shopify request!');
        }

        return $next($request);
    }
}