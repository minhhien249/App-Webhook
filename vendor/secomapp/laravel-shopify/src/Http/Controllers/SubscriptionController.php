<?php

namespace Secomapp\Http\Controllers;


use Secomapp\Pricing;
use Secomapp\Traits\InstalledShop;

class SubscriptionController
{
    use InstalledShop;

    /**
     * Show subscription.
     * GET /
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $shopName = $this->shopName();
        $subscription = $this->shop()->subscription();
        $plan = $subscription->plan;
        $charge = $subscription->charge;

        /** @var Pricing $pricing */
        $pricing = app(Pricing::class);
        $coupon = $pricing->getCoupon($shopName, $plan->id);
        $couponCode = $coupon ? $coupon->code : false;

        return view('laravel-shopify::subscription.index', compact('plan', 'charge', 'couponCode'));
    }
}