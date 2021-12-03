<?php

namespace Secomapp\Tests\Integration;


use Secomapp\Models\Plan;
use Secomapp\Models\Shop;
use Secomapp\Models\PlanFeature;
use Secomapp\Models\PlanSubscriptionUsage;
use Secomapp\Tests\TestCase;

class SubscriptionUsageMangerTest extends TestCase
{
    /**
     * Can subscription features usage.
     *
     * @test
     * @return void
     */
    public function it_can_record_usage()
    {
        $shop = Shop::create([
            'shop' => 'test.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'type' => 'recurring',
            'trial_days' => 15,
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'SAMPLE_SIMPLE_FEATURE', 'value' => 5])
        ]);

        $shop->newSubscription($plan)->create();

        // Record usage
        $usage = $shop->subscriptionUsage()->record('SAMPLE_SIMPLE_FEATURE')->fresh();

        $this->assertEquals(1, $shop->subscriptions()->get()->count());
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(1, $usage->used);
        $this->assertEquals(4, $shop->fresh()->subscription()->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Record usage by custom incremental amount
        $usage = $shop->subscriptionUsage()->record('SAMPLE_SIMPLE_FEATURE', 2)->fresh();
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(3, $usage->used);
        $this->assertEquals(2, $shop->fresh()->subscription()->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Record usage by fixed amount
        $usage = $shop->subscriptionUsage()->record('SAMPLE_SIMPLE_FEATURE', 2, false)->fresh();
        $this->assertInstanceOf(PlanSubscriptionUsage::class, $usage);
        $this->assertEquals(2, $usage->used);
        $this->assertEquals(3, $shop->fresh()->subscription()->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Reduce uses
        $usage = $shop->subscriptionUsage()->reduce('SAMPLE_SIMPLE_FEATURE')->fresh();
        $this->assertEquals(1, $usage->used);
        $this->assertEquals(4, $shop->fresh()->subscription()->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));

        // Clear usage
        $shop->subscriptionUsage()->clear();
        $this->assertEquals(0, $shop->subscription()->usage()->count());
        $this->assertEquals(5, $shop->fresh()->subscription()->ability()->remainings('SAMPLE_SIMPLE_FEATURE'));
    }
}
