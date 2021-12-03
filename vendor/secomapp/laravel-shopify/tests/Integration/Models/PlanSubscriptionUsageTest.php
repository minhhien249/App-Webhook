<?php

namespace Secomapp\Tests\Integration\Models;


use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Secomapp\Models\Plan;
use Secomapp\Models\Shop;
use Secomapp\Models\PlanFeature;
use Secomapp\Tests\TestCase;

class PlanSubscriptionUsageTest extends TestCase
{

    /**
     * Check if usage has expired.
     *
     * @test
     * @return void
     */
    public function it_can_check_if_usage_has_expired()
    {
        Config::set('billing.features', [
            'listings_per_month' => [
                'reseteable_interval' => 'month',
                'reseteable_count' => 1
            ]
        ]);

        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'interval' => 'month'
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50]),
        ]);

        $shop = Shop::create([
            'shop' => 'test.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $shop->newSubscription($plan)->create();

        $usage = $shop->subscriptionUsage()->record('listings_per_month');

        $this->assertFalse($usage->isExpired());

        $usage->valid_until = Carbon::now()->subDay(); // date is in the past by 1 day...

        $usage->save();

        $this->assertTrue($usage->isExpired());
    }
}
