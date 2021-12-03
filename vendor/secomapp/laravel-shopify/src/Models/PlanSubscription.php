<?php

namespace Secomapp\Models;

use LogicException;
use Carbon\Carbon;
use Secomapp\Period;
use Secomapp\SubscriptionAbility;
use Secomapp\SubscriptionUsageManager;
use Secomapp\Traits\BelongsToShop;
use Secomapp\Traits\BelongsToPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @mixin \Eloquent */
class PlanSubscription extends Model
{
    use BelongsToPlan, BelongsToShop;

    /**
     * Subscription statuses
     */
    const STATUS_ACTIVE   = 'active';
    const STATUS_CANCELED = 'canceled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shop_id',
        'plan_id',
        'coupon_code',
        'charge_id',
        'name',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'cancels_at',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at',
        'cancels_at', 'trial_ends_at', 'ends_at', 'starts_at',
    ];

    /**
     * Get charge.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    function charge()
    {
        return $this->belongsTo(Charge::class);
    }

    /**
     * Subscription Ability Manager instance.
     *
     * @var \Secomapp\SubscriptionAbility
     */
    protected $ability;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Set period if it wasn't set
            if (!$model->starts_at)
                $model->starts_at = Carbon::now();
        });
    }

    /**
     * Get subscription usage.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usage()
    {
        return $this->hasMany(PlanSubscriptionUsage::class, 'subscription_id');
    }


    /**
     * Get status attribute.
     *
     * @return string
     */
    public function getStatusAttribute()
    {
        if ($this->active())
            return self::STATUS_ACTIVE;

        if ($this->canceled())
            return self::STATUS_CANCELED;

        return self::STATUS_ACTIVE;
    }

    /**
     * Check if subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        if (!$this->ended() OR $this->onTrial() OR $this->plan->isFree())
            return true;

        return false;
    }

    /**
     * @param Charge $charge
     * @return PlanSubscription
     */
    public function activate($charge)
    {
        $this->charge()->associate($charge);
        $this->ends_at = null; // remove ends_at to allow use until they cancel
        $this->cancels_at = null;

        return $this;
    }

    /**
     * Check if subscription is charged
     *
     * @return bool
     */
    public function charged()
    {
        return $this->charge_id != null;
    }

    /**
     * Check if subscription is trialling.
     *
     * @return bool
     */
    public function onTrial()
    {
        if (!is_null($trialEndsAt = $this->trial_ends_at))
            return Carbon::instance($trialEndsAt)->isFuture();

        return false;
    }

    /**
     * Check if subscription is on grace period
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        return $this->canceled() && !$this->ended();
    }

    /**
     * Check if subscription is canceled.
     *
     * @return bool
     */
    public function canceled()
    {
        return !is_null($this->cancels_at);
    }

    /**
     * Check if subscription period has ended.
     *
     * @return bool
     */
    public function ended()
    {
        if (is_null($this->ends_at)) {
            return false;
        }

        $endsAt = Carbon::instance($this->ends_at);

        return Carbon::now()->gt($endsAt) OR Carbon::now()->eq($endsAt);
    }

    /**
     * Cancel subscription.
     *
     * @param  bool $immediately
     * @return $this
     */
    public function cancel($immediately = false)
    {
        $this->cancels_at = Carbon::now();

        // cancel charge
        $charge = $this->charge;
        if ($this->charged() && !$charge->canceled()) {
            $charge->cancel();
        }

        // set ends_at
        if ($immediately) {
            $this->ends_at = $this->cancels_at;
        } else {
            if (!$this->charged() || $this->plan->type == 'one-time' || $this->onTrial()) {
                $this->ends_at = $this->cancels_at;
            } else {
                $remainDays = Carbon::now()->diffInDays(Carbon::instance($this->trial_ends_at ?: $this->starts_at)) % 30;
                if ($remainDays == 0) $remainDays = 30;
                $this->ends_at = Carbon::now()->addDays($remainDays);
            }
        }

        return $this;
    }

    /**
     * Change subscription plan.
     *
     * @param mixed $plan     Plan Id or Plan Model Instance
     * @param mixed $discount Discount Id or Discount Model Instance
     * @return $this
     */
    public function changePlan($plan, $discount = NULL)
    {
        if (is_numeric($plan))
            $plan = Plan::find($plan);
        if (is_numeric($discount))
            $discount = Discount::find($discount);

        // TODO handle recurrence & one-time

        // If plans doesn't have the same billing frequency (e.g., interval
        // and interval_count) we will update the billing dates starting
        // today... and since we are basically creating a new billing cycle,
        // the usage data will be cleared.
        if (is_null($this->plan) OR $this->plan->type !== $plan->type) {
            // Set period
            if ($plan->type == 'one-time') {
                $this->starts_at = new Carbon;
                $this->ends_at = NULL;
            } else {
                $this->setNewPeriod();
            }

            // Clear usage data
            $usageManager = new SubscriptionUsageManager($this);
            $usageManager->clear();
        }

        // Attach new plan, discount to subscription
        $this->plan_id = $plan->id;
        if (is_null($discount)) {
            $this->coupon_code = NULL;
        } else {
            $this->coupon_code = $discount->id;
        }
        $this->charge_id = null;

        return $this;
    }

    /**
     * Renew subscription period.
     *
     * @return  $this
     */
    public function renew()
    {
        if ($this->ended() AND $this->canceled()) {
            throw new LogicException(
                'Unable to renew canceled ended subscription.'
            );
        }

        $subscription = $this;

        DB::transaction(function () use ($subscription) {
            // Clear usage data
            $usageManager = new SubscriptionUsageManager($subscription);
            $usageManager->clear();

            // Renew period
            $subscription->setNewPeriod();
            $subscription->cancels_at = null;
            $subscription->save();
        });

        return $this;
    }

    /**
     * Get Subscription Ability instance.
     *
     * @return \Secomapp\SubscriptionAbility
     */
    public function ability()
    {
        if (is_null($this->ability))
            return new SubscriptionAbility($this);

        return $this->ability;
    }

    /**
     * Find subscription with an ending trial.
     *
     * @param     $query
     * @param int $dayRange
     */
    public function scopeFindByEndingTrial($query, $dayRange = 3)
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        $query->whereBetween('trial_ends_at', [$from, $to]);
    }

    /**
     * Find subscription with an ended trial.
     *
     * @param $query
     */
    public function scopeFindByEndedTrial($query)
    {
        $query->where('trial_ends_at', '<=', date('Y-m-d H:i:s'));
    }

    /**
     * Find ending subscriptions.
     *
     * @param     $query
     * @param int $dayRange
     */
    public function scopeFindByEndingPeriod($query, $dayRange = 3)
    {
        $from = Carbon::now();
        $to = Carbon::now()->addDays($dayRange);

        $query->whereBetween('ends_at', [$from, $to]);
    }

    /**
     * Find ended subscriptions.
     *
     * @param $query
     */
    public function scopeFindByEndedPeriod($query)
    {
        $query->where('ends_at', '<=', date('Y-m-d H:i:s'));
    }


    /**
     * Set subscription period.
     *
     * @param  string $start Start date
     * @return  $this
     */
    protected function setNewPeriod($start = '')
    {
        $period = new Period('day', 30, $start);

        $this->starts_at = $period->getStartDate();
        $this->ends_at = $period->getEndDate();

        return $this;
    }
}
