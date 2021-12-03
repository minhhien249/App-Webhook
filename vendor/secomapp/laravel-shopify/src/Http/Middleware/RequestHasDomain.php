<?php

namespace Secomapp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Traits\InstalledShop;

class RequestHasDomain
{
    use InstalledShop;

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!$request->has('shop')) {
            Log::warning('shopify store domain required');

            return redirect()->route('add_app')->withMessage('shopify store domain required');
        }
        $shopName = shopNameFromDomain($request->input('shop'));
        $sessionShopName = $this->shopName();
        if ($sessionShopName && $shopName != $sessionShopName) {
            if (auth()->check()) {
                auth()->logout();
            }
            Log::info("domain change from {$sessionShopName}");

            // keep state so that user can install other shop without request twice
            $state = session('state');
            $request->session()->flush();
            if ($state) {
                session(['state' => $state]);
            }
        }

        return $next($request);
    }
}