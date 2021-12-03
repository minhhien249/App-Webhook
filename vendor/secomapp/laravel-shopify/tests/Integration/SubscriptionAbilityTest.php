<?php

namespace Secomapp\Tests\Integration\Models;


use Secomapp\Models\Plan;
use Secomapp\Tests\TestCase;
use Secomapp\Models\Shop;
use Secomapp\Models\PlanFeature;

class SubscriptionAbilityTest extends TestCase
{

    /**
     * Can check subscription feature usage.
     *
     * @test
     * @return void
     */
    public function it_can_check_feature_usage()
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
            new PlanFeature(['code' => 'listings', 'value' => 50]),
            new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10]),
            new PlanFeature(['code' => 'listing_title_bold', 'value' => 'N']),
            new PlanFeature(['code' => 'listing_video', 'value' => 'Y']),
        ]);

        // Create Subscription
        $shop->newSubscription($plan)->create();

        $this->assertTrue($shop->subscription()->ability()->canUse('listings'));
        $this->assertEquals(50, $shop->subscription()->ability()->remainings('listings'));
        $this->assertEquals(0, $shop->subscription()->ability()->consumed('listings'));
        $this->assertEquals(10, $shop->subscription()->ability()->value('pictures_per_listing'));
        $this->assertEquals('N', $shop->subscription()->ability()->value('listing_title_bold'));
        $this->assertFalse($shop->subscription()->ability()->enabled('listing_title_bold'));
        $this->assertTrue($shop->subscription()->ability()->enabled('listing_video'));
    }
}
