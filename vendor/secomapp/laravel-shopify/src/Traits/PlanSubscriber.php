<?php

namespace Secomapp\Traits;


use Secomapp\Models\PlanSubscription;
use Secomapp\SubscriptionBuilder;
use Secomapp\SubscriptionUsageManager;

trait PlanSubscriber
{

    /**
     * Get user plan subscription.
     *
     * @return \Secomapp\Models\PlanSubscription
     */
    public function subscriptions()
    {
        return $this->hasMany(PlanSubscription::class);
    }

    /**
     * Get a subscription by name.
     *
     * @param  string $name
     * @return \Secomapp\Models\PlanSubscription|null
     */
    public function subscription($name = 'main')
    {
        return $this->subscriptions()->get()->sortByDesc(function ($value) {
            return $value->created_at->getTimestamp();
        })->first(function ($value) use ($name) {
            return $value->name === $name;
        });
    }

    /**
     * Check if the user has a given subscription.
     *
     * @param  string $name
     * @return bool
     */
    public function subscribed($name = 'main')
    {
        $subscription = $this->subscription($name);

        if (is_null($subscription))
            return false;

        return $subscription->active();
    }

    /**
     * Subscribe user to a new plan.
     *
     * @param string $name
     * @param mixed  $plan
     * @return \Secomapp\SubscriptionBuilder
     */
    public function newSubscription($plan, $name = 'main')
    {
        return new SubscriptionBuilder($this, $name, $plan);
    }

    /**
     * Get subscription usage manager instance.
     *
     * @param  string $name
     * @return \Secomapp\SubscriptionUsageManager
     */
    public function subscriptionUsage($name = 'main')
    {
        return new SubscriptionUsageManager($this->subscription($name));
    }
}