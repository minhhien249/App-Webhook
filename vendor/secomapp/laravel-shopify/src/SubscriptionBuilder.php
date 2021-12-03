<?php

namespace Secomapp;


use Carbon\Carbon;
use Secomapp\Models\Coupon;
use Secomapp\Models\PlanSubscription;

class SubscriptionBuilder
{
    /**
     * The shop model that is subscribing.
     *
     * @var \Secomapp\Models\Shop
     */
    protected $shop;

    /**
     * The plan model that the shop is subscribing to.
     *
     * @var \Secomapp\Models\Plan
     */
    protected $plan;

    /**
     * The couponCode that the shop have
     */
    protected $couponCode;

    /**
     * The charge that the shop have
     * @var \Secomapp\Models\Charge
     */
    protected $charge;

    /**
     * The subscription name.
     *
     * @var string
     */
    protected $name;

    /**
     * Custom number of trial days to apply to the subscription.
     *
     * This will override the plan trial period.
     *
     * @var int|null
     */
    protected $trialDays;

    /**
     * Do not apply trial to the subscription.
     *
     * @var bool
     */
    protected $skipTrial = false;

    /**
     * Create a new subscription builder instance.
     *
     * @param  mixed  $shop
     * @param  string $name Subscription name
     * @param  mixed  $plan
     */
    public function __construct($shop, $name, $plan)
    {
        $this->shop = $shop;
        $this->name = $name;
        $this->plan = $plan;
    }

    public function withCouponCode($couponCode)
    {
        $this->couponCode = $couponCode;

        return $this;
    }

    public function withCharge($charge)
    {
        $this->charge = $charge;

        return $this;
    }

    /**
     * Specify the trial duration period in days.
     *
     * @param  int $trialDays
     * @return $this
     */
    public function trialDays($trialDays)
    {
        $this->trialDays = $trialDays;

        return $this;
    }

    /**
     * Do not apply trial to the subscription.
     *
     * @return $this
     */
    public function skipTrial()
    {
        $this->skipTrial = true;

        return $this;
    }

    /**
     * Create a new subscription.
     *
     * @param  array $attributes
     * @return PlanSubscription
     */
    public function create(array $attributes = [])
    {
        $now = Carbon::now();

        if ($this->skipTrial) {
            $trialEndsAt = null;
        } elseif ($this->trialDays) {
            $trialEndsAt = $now->addDays($this->trialDays);
        } elseif ($this->plan->hasTrial()) {
            $trialEndsAt = $now->addDays($this->plan->trial_days);
        } else {
            $trialEndsAt = $now;
        }

        /** @var Coupon $coupon */
        $coupon = null;
        if ($this->couponCode) {
            $coupon = Coupon::where('code', $this->couponCode)->first();
            if ($coupon && !$coupon->isCouponValid(shopNameFromDomain($this->shop->shop), $this->plan->id)) {
                $coupon = null;
            }
        }

        $subscription = $this->shop->subscriptions()->create(array_replace([
            'plan_id'       => $this->plan->id,
            'coupon_code'   => $coupon ? $this->couponCode : null,
            'charge_id'     => $this->charge ? $this->charge->id : null,
            'trial_ends_at' => $trialEndsAt,
            'name'          => $this->name,
        ], $attributes));

        if ($coupon) {
            $coupon->times_used++;
            $coupon->save();
        }

        return $subscription;
    }
}