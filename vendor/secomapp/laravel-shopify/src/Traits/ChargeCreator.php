<?php

namespace Secomapp\Traits;


use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Secomapp\Events\AppCharged;
use Secomapp\Events\AppChargePending;
use Secomapp\Events\AppFreeBasicStarted;
use Secomapp\Exceptions\ShopifyApiException;
use Secomapp\Models\Coupon;
use Secomapp\Models\Discount;
use Secomapp\Models\Plan;
use Secomapp\Pricing;
use Secomapp\Resources\ApplicationCharge;
use Secomapp\Contracts\ChargeContract;
use Secomapp\Resources\RecurringApplicationCharge;

trait ChargeCreator
{
    use InstalledShop;

    public function billing()
    {
        if (config('billing.free_forever', false)) {
            return $this->freeForever();
        } else {
            return $this->createCharge();
        }
    }

    public function switchToPaid()
    {
        $subscription = $this->shop()->subscription();
        if (!$subscription || !$subscription->active() || !$subscription->plan->isFree()) {
            return response()->message('The current plan is not free plan');
        } else {
            $shopName = $this->shopName();
            /** @var Pricing $pricing */
            $pricing = app(Pricing::class);
            $planId = $pricing->configPlan($this->shopPlan());
            /** @var Plan $plan */
            $plan = Plan::find($planId);

            if ($plan->isFree() && !$pricing->isTestShop($shopName)) {
                return response()->message('The current plan is not free plan');
            }

            return $this->createCharge($planId);
        }
    }

    /**
     * Create charge when install or in trial period (already have subscription)
     *
     * @param string|bool $planId
     * @param string      $couponCode
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function createCharge($planId = false, $couponCode = '')
    {
        Log::info('create charge');
        $shop = $this->shop();
        $shopName = shopNameFromDomain($shop->shop);
        $shopPlan = $this->shopPlan();

        // 1. determine plan
        /** @var Pricing $pricing */
        $pricing = app(Pricing::class);

        if (!$planId) {
            $planId = $pricing->determinePlan($shop, $shopPlan);
        }

        switch ($planId) {
            case -1:
                // don't allow affiliate shop to use
                Log::info('affiliate shop can not create charge');
                if (!session('sudo', false)) {
                    activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                        ->log('affiliate shop can not create charge');
                }

                return response()->message('Please upgrade your shop plan to use our app. Thank you.');
            case config('billing.free_basic_plan_id'):
                return $this->freeBasic();
        }

        /** @var Coupon $coupon */
        $coupon = $pricing->getCoupon($shopName, $planId, $couponCode);
        $plan = Plan::find($planId);

        // 2. create charge
        $charge = $pricing->computeCharge($shop, $plan, $coupon);

        $isOneTimeCharge = $charge->type == 'one-time';
        try {
            $state = Uuid::uuid4()->toString();
        } catch (\Exception $e) {
            $state = date("YmdHis", time());
        }
        $returnUrl = route('activate_charge')."?shop={$shop->shop}&state={$state}";

        $params = [
            'name'       => $charge->name,
            'price'      => $charge->price,
            'return_url' => $returnUrl,
            'test'       => $charge->test,
        ];
        /** @var ChargeContract $chargeApi */
        $chargeApi = $isOneTimeCharge ? app(ApplicationCharge::class) : app(RecurringApplicationCharge::class);
        if (!$isOneTimeCharge) {
            $params['trial_days'] = intval($charge->trial_days);
        }
        try {
            $chargeResponse = $chargeApi->create($params);
        } catch (ShopifyApiException $e) {
            Log::error("create {$charge->type} charge failed: {$e->getMessage()} {$e->getTraceAsString()}");

            return response()->message("Create charge failed. Please login and try again. Sorry for this problem");
        }
        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'shopify', 'shop' => $shopName])
                ->log(($isOneTimeCharge ? '' : 'recurring').' charge created');
        }

        // store both session & setting (verify in new session)
        session(['charge_plan_id' => $plan->id]);
        setting(['charge_plan_id' => $plan->id]);
        if ($coupon != null) {
            session(['charge_coupon_code' => $coupon->code]);
            setting(['charge_coupon_code' => $coupon->code]);
        }
        session(['charge_type' => $charge->type]);
        setting(['charge_type' => $charge->type]);
        session(['charge_shop_plan' => $shopPlan]);
        setting(['charge_shop_plan' => $shopPlan]);
        session(['state' => $state]);
        setting(['state' => $state]);

        if (!$isOneTimeCharge) {
            event(new AppChargePending());
        }

        return redirect($chargeResponse->confirmation_url);
    }

    public function freeForever()
    {
        Log::info('free plan');
        $shop = $this->shop();
        $shopName = shopNameFromDomain($shop->shop);

        $shop->deactivate();

        $plan = Plan::find(config('billing.free_plan_id', 1));
        $subscription = $shop->newSubscription($plan)->create();
        $shop->activate($subscription->starts_at)->save();

        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                ->log('free forever plan started');
        }

        return $this->homepage();
    }


    public function freeBasic()
    {
        Log::info('free basic plan');
        $shop = $this->shop();
        $shopName = shopNameFromDomain($shop->shop);

        $shop->deactivate();

        $plan = Plan::find(config('billing.free_basic_plan_id'));
        $subscription = $shop->newSubscription($plan)->create();
        $shop->activate($subscription->starts_at)->save();

        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                ->log('free basic plan started');
        }

        event(new AppFreeBasicStarted());

        return $this->homepage();
    }
}