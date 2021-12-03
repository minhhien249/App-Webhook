<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Traits\InstalledShop;

class AdminShopCheck
{
    use InstalledShop;

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $shopName = $this->shopName();

        if (!in_array($shopName, config('shopify.admin_shop_names'))) {
            Log::warning('unauthorized request');

            return response()->message('You do not have permission to access this page');
        }

        return $next($request);
    }
}