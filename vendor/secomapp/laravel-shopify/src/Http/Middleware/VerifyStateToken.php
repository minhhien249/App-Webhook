<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Models\Shop;

class VerifyStateToken
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $state = $request->input('state');
        $dbState = session('state');
        if (!$dbState) {
            Log::warning('request has state token not in session!');
            $shopDomain = $request->input('shop');
            $shop = Shop::where('shop', $shopDomain)->first();
            if ($shop) {
                $dbState = shopSetting($shop->id, 'state', false);
            }
        }
        if ($dbState != $state) {
            $request->session()->forget('state');
            Log::warning('request has invalid state token!');

            return response()->message('request has invalid state token!');
        }
        $request->session()->forget('state');

        return $next($request);
    }
}