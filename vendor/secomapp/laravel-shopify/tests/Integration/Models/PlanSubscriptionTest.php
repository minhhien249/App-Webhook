<?php

namespace Secomapp\Tests\Integration\Models;


use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Secomapp\Models\Charge;
use Secomapp\Period;
use Secomapp\Models\Shop;
use Secomapp\Models\Plan;
use Secomapp\Models\PlanFeature;
use Secomapp\Models\PlanSubscription;
use Secomapp\Tests\TestCase;

class PlanSubscriptionTest extends TestCase
{

    protected $plan;
    protected $shop;
    protected $subscription;

    /**
     * Setup test
     *
     * @return  void
     */
    public function setUp()
    {
        parent::setUp();

        Config::set('billing.features', [
            'listings_per_month' => [
                'reseteable_interval' => 'month',
                'reseteable_count' => 1
            ],
            'pictures_per_listing',
            'listing_duration_days',
            'listing_title_bold'
        ]);

        $this->plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'type' => 'recurring',
            'trial_days' => 14,
        ]);

        $this->plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50]),
            new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10]),
            new PlanFeature(['code' => 'listing_duration_days', 'value' => 30]),
            new PlanFeature(['code' => 'listing_title_bold', 'value' => 'N']),
        ]);

        $this->shop = Shop::create([
            'shop' => 'test.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $this->shop->newSubscription($this->plan)->create();

        $this->subscription = $this->shop->subscription();
    }

    /**
     * Test the start_at value right after subscription start.
     *
     * @test
     * @return void
     */
    public function starts_at_value()
    {
        $this->shop->activated_at = $this->subscription->starts_at;
        $this->shop->save();
        $shop = Shop::find($this->shop->id);
        $this->assertEquals($shop->activated_at, $this->shop->activated_at);
    }

    /**
     * Can get subscription shop.
     *
     * @test
     * @return void
     */
    public function it_can_get_subscription_shop()
    {
        $this->assertInstanceOf(Shop::class, $this->subscription->shop);
    }

    /**
     * Can check if subscription is active.
     *
     * @test
     * @return void
     */
    public function it_is_active()
    {
        $this->assertTrue($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);
    }

    /**
     * Can check if subscription is canceled.
     *
     * @test
     * @return void
     */
    public function it_is_canceled()
    {
        // Cancel subscription at period end...
        $this->subscription->cancel();
        $this->subscription->trial_ends_at = null;

        $this->assertTrue($this->subscription->canceled());
        $this->assertFalse($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_CANCELED, $this->subscription->status);

        // Cancel subscription immediately...
        $this->subscription->cancel(true);

        $this->assertTrue($this->subscription->canceled());
        $this->assertFalse($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_CANCELED, $this->subscription->status);
    }

    /**
     * Can check if subscription is trialling.
     *
     * @test
     * @return void
     */
    public function it_is_trialling()
    {
        // Test if subscription is active after applying a trial.
        $this->subscription->trial_ends_at = $this->subscription->trial_ends_at->addDays(2);
        $this->assertTrue($this->subscription->active());
        $this->assertEquals(PlanSubscription::STATUS_ACTIVE, $this->subscription->status);

        // Test if subscription is inactive after removing the trial.
        $this->subscription->trial_ends_at = Carbon::now()->subDay();
        $this->subscription->cancel(true);
        $this->assertFalse($this->subscription->active());
    }

    /**
     * Can be renewed.
     *
     * @test
     * @return void
     */
    public function it_can_be_renewed()
    {
        // Create a subscription with an ended period...
        $subscription = factory(PlanSubscription::class)->create([
            'plan_id' => factory(Plan::class)->create([
                'type' => 'recurring',
                'price' => 1.0
            ])->id,
            'trial_ends_at' => Carbon::now()->subDays(30),
            'ends_at' => Carbon::now()->subDays(30),
        ]);

        $this->assertFalse($subscription->active());

        $subscription->renew();

        $this->assertTrue($subscription->active());
        $this->assertEquals(Carbon::now()->addDays(30), $subscription->ends_at);
    }

    /**
     * Can find subscription with an ending trial.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ending_trial()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'trial_ends_at' => Carbon::now()->addDays(10) // End in ten days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'trial_ends_at' => Carbon::now()->addDays(3), // Ended a day ago...
        ]);

        $result = PlanSubscription::findByEndingTrial(3)->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ended trial.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ended_trial()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'trial_ends_at' => Carbon::now()->addDays(2) // End in two days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'trial_ends_at' => Carbon::now()->subDay(), // Ended a day ago...
        ]);

        $result = PlanSubscription::findByEndedTrial()->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ending period.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ending_period()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'ends_at' => Carbon::now()->addDays(10) // End in ten days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'ends_at' => Carbon::now()->addDays(3), // Ended a day ago...
        ]);

        $result = PlanSubscription::findByEndingPeriod(3)->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can find subscription with an ended period.
     *
     * @test
     * @return void
     */
    public function it_can_find_subscriptions_with_ended_period()
    {
        // For "control", these subscription shouldn't be
        // included in the result...
        factory(PlanSubscription::class, 10)->create([
            'ends_at' => Carbon::now()->addDays(2) // End in two days...
        ]);

        // These are the results that should be returned...
        factory(PlanSubscription::class, 5)->create([
            'ends_at' => Carbon::now()->subDay(), // Ended a day ago...
        ]);

        $result = PlanSubscription::findByEndedPeriod()->get();

        $this->assertEquals(5, $result->count());
    }

    /**
     * Can change subscription plan.
     *
     * @test
     * @return void
     */
    public function it_can_change_plan()
    {
        $newPlan = Plan::create([
            'name' => 'Business',
            'description' => 'Business plan',
            'price' => 49.89,
            'type' => 'recurring',
            'trial_days' => 30,
        ]);

        $newPlan->features()->saveMany([
            new PlanFeature(['code' => 'listing_title_bold', 'value' => 'Y']),
        ]);

        // Change plan
        $this->subscription->changePlan($newPlan)->save();

        // Plan was changed?
        $this->assertEquals('Business', $this->subscription->fresh()->plan->name);

        // Let's check if the subscription period was set
        $period = new Period('day', 30);

        // Expected dates
        $expectedPeriodStartDate = $period->getStartDate();
        $expectedPeriodEndDate = $period->getEndDate();

        // This assertion will make sure that the subscription is now using
        // the new plan features...
        $this->assertEquals('Y', $this->subscription->fresh()->ability()->value('listing_title_bold'));
    }

    /**
     * Cancel a subscription and set ends_at correctly
     *
     * @test
     * @return void
     */
    public function it_cancel_on_trial()
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'type' => 'recurring',
            'trial_days' => 14,
        ]);

        $shop = Shop::create([
            'shop' => 'test2.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $charge = Charge::create([
            'charge_id' => 11111,
            'shop_id' => $shop->id,
            'name' => 'Basic',
            'price' => 9.99,
            'trial_days' => 14,
            'type' => 'recurring',
            'created_at' => '2016-01-01 00:00:00',
            'billing_on' => '2016-02-01 00:00:00',
            'test' => true,
            'status' => 'active',
            'description' => 'XXX'
        ]);

        $shop->newSubscription($plan)->withCharge($charge)->create();

        $subscription = $shop->subscription();

        $this->assertEquals(Carbon::now()->addDays(14), $subscription->trial_ends_at);

        $subscription->cancel()->save();

        $this->assertEquals(Carbon::now(), $subscription->cancels_at);
        $this->assertEquals(Carbon::now(), $subscription->ends_at);
    }

    /**
     * Cancel a subscription and set ends_at correctly
     *
     * @test
     * @return void
     */
    public function it_cancel_on_paid()
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'type' => 'recurring',
            'trial_days' => 0,
        ]);

        $shop = Shop::create([
            'shop' => 'test2.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $charge = Charge::create([
            'charge_id' => 11111,
            'shop_id' => $shop->id,
            'name' => 'Basic',
            'price' => 9.99,
            'trial_days' => 14,
            'type' => 'recurring',
            'created_at' => '2016-01-01 00:00:00',
            'billing_on' => '2016-02-01 00:00:00',
            'test' => true,
            'status' => 'active',
            'description' => 'XXX'
        ]);

        $shop->newSubscription($plan)->withCharge($charge)->create();

        $subscription = $shop->subscription();

        $this->assertNull($subscription->trial_ends_at);

        $subscription->cancel()->save();

        $this->assertEquals(Carbon::now(), $subscription->cancels_at);
        $this->assertEquals(Carbon::now()->addDays(30), $subscription->ends_at);
    }
}
