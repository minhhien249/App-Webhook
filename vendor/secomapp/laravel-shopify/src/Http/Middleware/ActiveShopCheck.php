<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Traits\ChargeCreator;

class ActiveShopCheck
{
    use ChargeCreator;

    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $shop = $this->shop();
        if ($shop->uninstalled()) {
            return response()->message('You already uninstalled the app');
        }
        $subscription = $shop->subscription();
        $sudo = session('sudo', false);
        if (!$sudo && (!$subscription || $subscription->canceled())) {
            Log::info('app is not active');

            return $this->billing();
        }

        return $next($request);
    }
}