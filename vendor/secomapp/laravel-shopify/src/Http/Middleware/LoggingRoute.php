<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Traits\InstalledShop;

class LoggingRoute
{
    use InstalledShop;

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $path = $request->path();
        $method = $request->method();

        if (!session('sudo', false)) {
            $shopName = $this->shopName();
            if (!$shopName || !in_array($shopName, config('shopify.admin_shop_names'))) {
                Log::info("{$method} {$path}");
            }
        }

        return $next($request);
    }
}

