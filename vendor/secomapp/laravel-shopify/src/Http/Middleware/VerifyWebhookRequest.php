<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyWebhookRequest
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!isValidWebhookRequest($request)) {
            $content = $request->getContent();
            Log::error("uninstalled webhook bad request : {$content}");

            return response()->message('uninstalled webhook bad request!');
        }

        return $next($request);
    }
}