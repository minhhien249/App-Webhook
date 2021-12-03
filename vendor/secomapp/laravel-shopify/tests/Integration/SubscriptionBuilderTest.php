<?php

namespace Secomapp\Tests\Integration\Models;


use Secomapp\Models\Discount;
use Secomapp\Models\Plan;
use Secomapp\Models\Shop;
use Secomapp\Tests\TestCase;

class SubscriptionBuilderTest extends TestCase
{
    /**
     * Can create new user subscription.
     *
     * @test
     * @return void
     */
    public function it_can_create_new_subscriptions()
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
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 15,
        ]);

        // Create Subscription
        $shop->newSubscription($plan, 'main')->create();
        $shop->newSubscription($plan, 'second')->create([
            'name' => 'override' // test if data can be override
        ]);

        $this->assertNull($shop->subscription('main')->discount);

        $this->assertEquals(2, $shop->subscriptions->count());
        $this->assertEquals('main', $shop->subscription('main')->name);
        $this->assertEquals('override', $shop->subscription('override')->name);
        $this->assertTrue($shop->subscribed('main'));
        $this->assertTrue($shop->subscribed('override'));
    }

    /**
     * Can create new user subscription.
     *
     * @test
     * @return void
     */
    public function it_can_create_new_subscriptions_with_discount()
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
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 15,
        ]);

        $discount = Discount::create([
            'shop' => 'test.myshopify.com',
            'name' => 'ABC',
            'type' => 'amount',
            'value' => 50,
            'status' => 1
        ]);

        // Create Subscription
        $shop->newSubscription($plan, 'main')->withDiscount($discount)->create();
        $shop->newSubscription($plan, 'second')->withDiscount($discount)->create([
            'name' => 'override' // test if data can be override
        ]);

        $registerDiscount = $shop->subscription('main')->discount;

        $this->assertEquals($discount->value, $registerDiscount->value);

        $this->assertEquals(2, $shop->subscriptions()->count());
        $this->assertEquals('main', $shop->subscription('main')->name);
        $this->assertEquals('override', $shop->subscription('override')->name);
        $this->assertTrue($shop->subscribed('main'));
        $this->assertTrue($shop->subscribed('override'));
    }
}
