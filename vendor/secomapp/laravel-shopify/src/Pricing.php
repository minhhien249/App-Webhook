<?php

namespace Secomapp;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Secomapp\Contracts\ClientApiContract;
use Secomapp\Exceptions\ShopifyApiException;
use Secomapp\Facades\ShopSetting;
use Secomapp\Models\Coupon;
use Secomapp\Models\Plan;
use Secomapp\Models\Shop;
use Secomapp\Resources\Order;

class Pricing
{
    /**
     * @var ClientApiContract
     */
    private $clientApi;

    public function __construct(ClientApiContract $clientApi)
    {
        $this->clientApi = $clientApi;
    }

    /**
     * Verify shop plan changed.
     *
     * @param Shop $shop
     * @param string $shopPlan
     * @return bool
     */
    public function verifyPlan($shop, $shopPlan)
    {
        $shopName = shopNameFromDomain($shop->shop);

        $subscription = $shop->subscription();
        if (!$subscription) {
            return false;
        }
        if ($subscription->plan->id != config('billing.free_basic_plan_id')) {
            return true;
        }

        $planId = $this->determinePlan($shop, $shopPlan);
        if ($planId == config('billing.free_basic_plan_id')) {
            return true;
        }

        $subscription->cancel()->save();

        Log::info('free basic plan end');
        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                ->log('free basic plan end');
        }

        return false;
    }

    /**
     * @param string $shopName
     * @param string $shopPlan
     *
     * @return Plan plan
     */
    public function determinePlan($shopName, $shopPlan)
    {
        $planId = $this->configPlan($shopPlan);

        Log::info("store plan = {$shopPlan} plan id = {$planId}");
        switch ($planId) {
            case -1: // check if shop is our shop
                if ($this->isTestShop($shopName)) {
                    $planId = config('billing.basic_plan_id');
                }
                break;
            case config('billing.basic_plan_id'):
                try {
                    /** @var Order $orderApi */
                    $orderApi = new Order($this->clientApi);
                    $orderCount = $orderApi->count([
                        'created_at_min' => Carbon::now()->subDays(30)->toIso8601String(),
                    ]);
                    Log::info("number of orders = {$orderCount}");
                    if ($orderCount <= config('billing.free_basic_no_order_threshold')) {
                        $planId = config('billing.free_basic_plan_id'); // free
                    }
                } catch (ShopifyApiException $e) {
                    Log::error("get order count failed : {$e->getMessage()} {$e->getTraceAsString()}");
                }

                break;
        }

        return $planId;
    }

    public function configPlan($shopPlan)
    {
        $planMap = config('billing.plan_map');
        if (array_key_exists($shopPlan, $planMap)) {
            $planId = $planMap[$shopPlan];
        } else {
            $planId = config('billing.basic_plan_id');
        }

        return $planId;
    }

    /**
     * Check if a shop allow to test in prod
     *
     * @param $shopName
     * @return bool
     */
    public function isTestShop($shopName)
    {
        $test = config('billing.test');

        if (!$test) {
            // allowing some dev shop to install
            $devShopNames = explode(' ', config('billing.dev_shop_names'));
            if (in_array($shopName, $devShopNames)) {
                $test = true;
            }
        }

        return $test;
    }

    /**
     * Get current shop plan. Take into account any discount.
     *
     * @param Shop $shop
     * @param string $shopPlan
     *
     * @return Plan plan
     */
    public function currentPlan($shop, $shopPlan)
    {
        $shopName = shopNameFromDomain($shop->shop);
        $planId = $this->determinePlan($shopName, $shopPlan);
        $plan = Plan::find($planId);
        $coupon = $this->getCoupon($shopName, $planId);
        $plan = $this->discountedPlan($shop, $plan, $coupon);

        return $plan;
    }

    /**
     * @param string $shopName
     * @param string $planId
     * @param string|bool $couponCode
     * @return Coupon|bool
     */
    public function getCoupon($shopName, $planId, $couponCode = false)
    {
        if (!$couponCode) {
            $couponCode = session('coupon_code'); // get coupon code form add_app page
            if ($couponCode) {
                setting(['install_coupon_code' => $couponCode]); // save install coupon for use later
            } else {
                $couponCode = $this->getAutomaticCoupon($shopName, $planId); // get predefined coupon code for shop so customer don't need to enter coupon code
            }
        }

        if (!$couponCode) {
            return false;
        }

        /** @var Coupon $coupon */
        $coupon = Coupon::where('code', $couponCode)->first();
        if (!$coupon || !$coupon->isCouponValid($shopName, $planId)) {
            return false;
        }

        return $coupon;
    }

    /**
     * @param string $shopName
     * @param string $planId
     *
     * @return string coupon code
     */
    private function getAutomaticCoupon($shopName, $planId)
    {
        $coupon = false;
        $couponCode = setting('install_coupon_code');
        if ($couponCode) {
            $coupon = $this->getActiveCoupon($couponCode, $shopName, $planId);
        }

        if (!$coupon) {
            $couponCode = config("coupon.{$shopName}");
            if ($couponCode) {
                $coupon = $this->getActiveCoupon($couponCode, $shopName, $planId);
            }
        }

        if (!$coupon) {
            $coupon = DB::table('coupons')
                ->join('discounts', 'coupons.discount_id', '=', 'discounts.id')
                ->select('coupons.code')
                ->whereRaw("(discounts.plan_id IS NULL OR discounts.plan_id = {$planId})")
                ->whereRaw('(discounts.started_at IS NULL OR discounts.started_at <= NOW())')
                ->whereRaw('(discounts.expired_at IS NULL OR discounts.expired_at >= NOW())')
                ->whereRaw('(discounts.usage_limit IS NULL OR coupons.times_used < discounts.usage_limit)')
                ->whereRaw("(coupons.shop='{$shopName}' OR coupons.shop='{$shopName}.myshopify.com')")
                ->where('coupons.status', true)
                ->where('coupons.automatic', true)
                ->orderBy('coupons.created_at', 'DESC')
                ->select(['code'])->first();
        }

        return $coupon ? $coupon->code : null;
    }

    private function getActiveCoupon($couponCode, $shopName, $planId) {
        /** @var Coupon $coupon */
        $coupon = Coupon::where('code', $couponCode)->first();
        if ($coupon && !$coupon->isCouponValid($shopName, $planId)) {
            return false;
        }

        return $coupon;
    }

    /**
     * Update plan with discount info, used days
     *
     * @param Shop $shop
     * @param Plan $plan
     * @param Coupon $coupon
     *
     * @return Plan
     */
    private function discountedPlan($shop, $plan, $coupon = null)
    {
        $planTrialDays = $plan->trial_days;

        $usedDays = $this->getUsedDays($shop);
        $trialDays = $plan->trial_days - $usedDays;

        // add grace period in trial_days
        if (ShopSetting::has('subscription_ends_at', $shop->id)) {
            $graceDays = Carbon::now()->diffInDays(Carbon::parse(setting('subscription_ends_at')));
            if ($graceDays >= 0) {
                $trialDays += $graceDays;
            }
        }

        // we allow to use 14 days free trial for shop that just go out of basic free trials
        $subscription = $shop->subscription();
        $freeBasicExpired = $subscription && $subscription->ended() &&
            $subscription->plan_id == config('billing.free_basic_plan_id');

        // if no discount, only update used days
        if (!$coupon) {
            $plan->trial_days = $trialDays >= 1 ? $trialDays : 1;
            if ($freeBasicExpired) {
                if ($plan->trial_days < $planTrialDays) $plan->trial_days = $planTrialDays;
            }

            return $plan;
        }

        if ($trialDays < 0) $trialDays = 0;

        $discount = $coupon->discount;
        // compute discount used days
        if ($discount->trial_days) {
            $trialDays = $discount->trial_days;
        }
        $plan->discount_trial_days = $trialDays >= 1 ? $trialDays : 1;
        if ($freeBasicExpired) {
            if ($plan->discount_trial_days < $planTrialDays) $plan->discount_trial_days = $planTrialDays;
        }

        // compute discount price
        $price = $plan->price;
        if ($discount->type == 'amount') {
            $price -= $discount->value;
        } else {
            $price = round((1.0 - $discount->value / 100.0) * $price, 2);
        }
        if ($price <= 0) {
            $price = 0;
        }
        $plan->discount_price = $price;

        // add discount id
        $plan->discount_id = $discount->id;

        return $plan;
    }

    /**
     * Compute the used days.
     *
     * @desc getUsedDays get all used days from the previous subscription with paid plan.
     * The method always rounds the used days to lower value.
     * @param Shop $shop
     * @return integer trial days
     */
    private function getUsedDays($shop)
    {
        $usedDays = 0;
        $chargedSubscriptions = DB::table('plan_subscriptions')
            ->join('shops', 'plan_subscriptions.shop_id', '=', 'shops.id')
            ->join('plans', 'plan_subscriptions.plan_id', '=', 'plans.id')
            ->where('plans.price', '>', 0.00)
            ->where('shops.id', '=', $shop->id)
            ->get(['starts_at', 'ends_at']);

        foreach ($chargedSubscriptions as $chargedSubscription) {
            /** @var Carbon $endsAt */
            $endsAt = Carbon::parse($chargedSubscription->ends_at);
            /** @var Carbon $startsAt */
            $startsAt = Carbon::parse($chargedSubscription->starts_at);
            $usedDays += $endsAt->diffInDays($startsAt);
        }

        return $usedDays;
    }

    /**
     * @param Plan $plan
     *
     * @return array
     */
    public function getPlanFeatures($plan)
    {
        $features = [];

        if (key_exists($plan->id, config('billing.plan_shopify_plan_map'))) {
            $shopifyPlanName = config('billing.plan_shopify_plan_map')[$plan->id];
        } else {
            $shopifyPlanName = '*';
        }
        $features['Store\'s Shopify Plan'] = $shopifyPlanName;

        switch ($plan->id) {
            case config('billing.basic_plan_id'):
                $features['Number of Orders'] = '> ' . config('billing.free_basic_no_order_threshold') . ' orders';
                break;
            case config('billing.free_basic_plan_id'):
                $features['Number of Orders'] = '<= ' . config('billing.free_basic_no_order_threshold') . ' orders';
                break;
        }

        return $features;
    }

    /**
     * Get current shop plan. Take into account any discount.
     *
     * @param Shop $shop
     * @param string $shopPlan
     *
     * @return Plan plan
     */
    public function paidPlan($shop, $shopPlan)
    {
        $shopName = shopNameFromDomain($shop->shop);
        $planId = $planId = $this->configPlan($shopPlan);
        $plan = Plan::find($planId);
        $coupon = $this->getCoupon($shopName, $planId);
        $plan = $this->discountedPlan($shop, $plan, $coupon);

        return $plan;
    }

    /**
     * Get charge info taking into account the discount.
     *
     * @param Shop $shop
     * @param Plan $plan
     * @param Coupon $coupon
     *
     * @return \stdClass
     */
    public function computeCharge($shop, $plan, $coupon = null)
    {
        $shopName = shopNameFromDomain($shop->shop);

        $plan = $this->discountedPlan($shop, $plan, $coupon);

        if ($coupon) {
            $charge = [
                'name' => "$plan->name with Coupon {$coupon->code}",
                'discount_id' => $plan->discount_id,
                'price' => $plan->discount_price,
                'trial_days' => $plan->discount_trial_days,
                'type' => $plan->type,
                'test' => $this->isTestShop($shopName),
            ];
        } else {
            $charge = [
                'name' => $plan->name,
                'price' => $plan->price,
                'trial_days' => $plan->trial_days,
                'type' => $plan->type,
                'test' => $this->isTestShop($shopName),
            ];
        }

        return (object)$charge;
    }
}