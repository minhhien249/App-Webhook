<?php

namespace Secomapp\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Secomapp\Pricing;
use Secomapp\Traits\ChargeCreator;

class VerifyPlan
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
        if (session('verify_plan') !== true) {
            $shop = $this->shop();
            /** @var Pricing $pricing */
            $pricing = app(Pricing::class);
            if (!$pricing->verifyPlan($shop, $this->shopPlan())) {
                Log::info('shop plan change');

                return $this->billing();
            }
            session('verify_plan', true);
        }

        return $next($request);
    }
}