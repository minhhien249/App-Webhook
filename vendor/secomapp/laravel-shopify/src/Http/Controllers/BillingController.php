<?php

namespace Secomapp\Http\Controllers;


use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Secomapp\ClientApi;
use Secomapp\Contracts\ChargeContract;
use Secomapp\Events\AppCharged;
use Secomapp\Exceptions\ShopifyApiException;
use Secomapp\Models\Charge;
use Secomapp\Models\Coupon;
use Secomapp\Models\Plan;
use Secomapp\Models\PlanSubscription;
use Secomapp\Models\Shop;
use Secomapp\Models\User;
use Secomapp\Pricing;
use Secomapp\Resources\ApplicationCharge;
use Secomapp\Resources\RecurringApplicationCharge;
use Secomapp\Traits\ChargeCreator;

class BillingController extends Controller
{
    use ChargeCreator;

    /**
     * Show the pricing plan
     * GET /pricing
     *
     * @return View
     */
    public function getPricing()
    {
        $shop = $this->shop();

        $plans = Plan::where('status', true)->where('id', '>=', 10)->get();
        /** @var Pricing $pricing */
        $pricing = app(Pricing::class);
        $currentPlan = $pricing->currentPlan($shop, $this->shopPlan());

        $planFeatures = [];
        foreach ($plans as $plan) {
            $features = $pricing->getPlanFeatures($plan);
            foreach ($features as $key => $value) {
                if (isset($planFeatures[$key])) {
                    $values = $planFeatures[$key];
                } else {
                    $values = [];
                }
                $values[$plan->id] = $value;
                $planFeatures[$key] = $values;
            }
        }

        return view('laravel-shopify::billing/pricing', compact('currentPlan', 'plans', 'planFeatures'));
    }

    /**
     * Creating charge
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postPricing()
    {
        return $this->createCharge();
    }

    /**
     * Switch to paid plan
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postSwitchToPaid()
    {
        return $this->switchToPaid();
    }


    /**
     * Activate application charge.
     * GET /activate_charge
     *
     * @param $request
     * @return Response | \Illuminate\Routing\Redirector
     */
    public function activateCharge(Request $request)
    {
        Log::info('confirm charge');
        $domain = $request->input('shop');
        $shopName = shopNameFromDomain($domain);
        $chargeId = $request->input('charge_id');
        try {
        if (auth()->check()) {
            $shop = $this->shop();
            $chargeType = session('charge_type');
            $chargePlanId = session('charge_plan_id');
            $chargeCouponCode = session('charge_coupon_code', false);
            // get charge status
            $isOneTime = $chargeType == 'one-time';
            /** @var ChargeContract $chargeApi */
            $chargeApi = $isOneTime ? app(ApplicationCharge::class) : app(RecurringApplicationCharge::class);
        } else {
            $shop = Shop::findByDomain($domain)->first();
            if (!$shop) {
                Log::error("Shop not found: " . shopNameFromDomain($domain));

                return redirect()->route('add_app_post')->with(['shop' => $domain]);
            }
            $chargeType = shopSetting($shop->id,'charge_type');
            $chargePlanId = shopSetting($shop->id,'charge_plan_id');
            $chargeCouponCode = shopSetting($shop->id,'charge_coupon_code', false);

            // get charge status
            $isOneTime = $chargeType == 'one-time';
            $accessToken = $shop->access_token;
            $client = new ClientApi(config('shopify.shared_secret'), $shopName, $accessToken);
            /** @var ChargeContract $chargeApi */
            $chargeApi = $isOneTime ? new ApplicationCharge($client) : new RecurringApplicationCharge($client);
        }

        try {
            $chargeResponse = $chargeApi->get($chargeId);
            $chargeStatus = $chargeResponse->status;
        } catch (ShopifyApiException $e) {
            Log::error("get charge failed: {$e->getMessage()} {$e->getTraceAsString()}");

            return $this->billing();
        }

        // charge is denied
        if (!$chargeApi->activeStatus($chargeStatus)) {
            Log::info("charge is {$chargeStatus}");
            if (!session('sudo', false)) {
                activity()->causedBy(auth()->user())->withProperties(['layer' => 'shopify', 'shop' => $shopName])
                    ->log(($isOneTime ? 'charge ' : 'recurring ') . $chargeStatus);
            }

            $subscription = $shop->subscription();
            // no trial left
            if (!$subscription || !$subscription->active()) {
                Log::info('app is not active');
                if (!session('sudo', false)) {
                    activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                        ->log('app is not active');
                }

                return response()->message('you should accept charge to start using our app!');
            }

            if (!session('sudo', false)) {
                activity()->causedBy(auth()->user())->withProperty('layer', 'app')
                    ->log("app trial started");
            }

            return $this->homepage();
        }

        // activate charge
        try {
            $appCharge = $chargeApi->activate($chargeId);
        } catch (ShopifyApiException $e) {
            Log::error("charge activation failed: {$e->getMessage()} {$e->getTraceAsString()}");
            if (!session('sudo', false)) {
                activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                    ->log(($isOneTime ? '' : 'recurring ') . 'charge activation failed');
            }

            return response()->message('charge activation failed!');
        }
        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'shopify', 'shop' => $shopName])
                ->log(($isOneTime ? '' : 'recurring') . " charge $chargeStatus");
        }


        // save charge info
        $charge = Charge::createFrom($shop->id, $chargeType, $appCharge);

        $shop->deactivate();

        $plan = Plan::find($chargePlanId);
        $subscription = $shop->newSubscription($plan)
            ->withCouponCode($chargeCouponCode)
            ->withCharge($charge)
            ->create();

        $shop->activate($subscription->starts_at)->save();

        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                ->log("app activated");
        }

        if (!auth()->check()) {
            $user = User::findByShopId($shop->id)->first();
            auth()->login($user);
        }

        event(new AppCharged($charge));

        return $this->homepage();
        } catch (\Exception $e) {
            Log::error($shopName." charge activation failed: {$e->getMessage()} {$e->getTraceAsString()}");
        }
    }

    /**
     * Show the pricing plan
     * GET /discount
     *
     * @param string $couponCode
     * @return mixed
     */
    public function getDiscount($couponCode)
    {
        $shopName = $this->shopName();
        $shop = $this->shop();
        $shopPlan = $this->shopPlan();

        /** @var Pricing $pricing */
        $pricing = app(Pricing::class);

        /** @var PlanSubscription $subscription */
        $subscription = $shop->subscription();
        if ($subscription && $subscription->active()) {
            $planId = $subscription->plan->id;
        } else {
            $planId = $pricing->determinePlan($shop, $shopPlan);
        }

        /** @var Coupon $coupon */
        $coupon = $pricing->getCoupon($shopName, $planId, $couponCode);
        if (!$coupon) {
            Log::warning("{$shopName}: The discount code {$couponCode} is not valid");

            return redirect()->route('subscription')
                ->withErrors(["The discount code {$couponCode} is not valid"]);
        }

        if ($subscription && $subscription->active() && $subscription->charge && $subscription->charge->coupon_code === $couponCode) {
            Log::warning("{$shopName}: The discount code {$couponCode} was already applied");

            return redirect()->route('subscription')
                ->withErrors(["The discount code {$couponCode} was already applied"]);
        }

        $plan = Plan::find($planId);
        $charge = $pricing->computeCharge($shop, $plan, $coupon);
        $features = $pricing->getPlanFeatures($plan);
        $hasDiscount = $plan->price > $charge->price;

        return view('laravel-shopify::billing.discount', compact('couponCode', 'plan', 'coupon', 'charge', 'features', 'hasDiscount'));
    }

    /**
     * Process confirm discount
     * POST /discount
     *
     * @param string $couponCode
     * @return mixed
     */
    public function postDiscount($couponCode)
    {
        $shopName = $this->shopName();
        $shop = $this->shop();
        $shopPlan = $this->shopPlan();

        /** @var Pricing $pricing */
        $pricing = app(Pricing::class);

        /** @var PlanSubscription $subscription */
        $subscription = $shop->subscription();
        if ($subscription && $subscription->active()) {
            $planId = $subscription->plan->id;
        } else {
            $planId = $pricing->determinePlan($shop, $shopPlan);
        }

        $coupon = $pricing->getCoupon($shopName, $planId, $couponCode);
        if (!$coupon) {
            Log::warning("{$shopName}: The discount code {$couponCode} is not valid");

            return redirect()->route('subscription')
                ->withErrors(["The discount code {$couponCode} is not valid"]);
        }

        return $this->createCharge($planId, $couponCode);
    }
}