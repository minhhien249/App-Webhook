<?php

namespace Secomapp\Tests\Integration\Models;

use Illuminate\Support\Facades\Config;
use Secomapp\Models\Charge;
use Secomapp\Models\Plan;
use Secomapp\Models\PlanFeature;
use Secomapp\Models\Shop;
use Secomapp\Tests\TestCase;

class ChargeTest extends TestCase
{

    /**
     * Can create a discount and find it.
     *
     * @test
     * @return void
     */
    public function apply_charge()
    {
        Config::set('billing.features', [
            'listings_per_month' => [
                'reseteable_interval' => 'month',
                'reseteable_count' => 1
            ],
            'pictures_per_listing',
            'listing_duration_days',
            'listing_title_bold'
        ]);

        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'type' => 'recurring',
            'trial_days' => 15,
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50]),
            new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10]),
            new PlanFeature(['code' => 'listing_duration_days', 'value' => 30]),
            new PlanFeature(['code' => 'listing_title_bold', 'value' => 'N']),
        ]);

        $shop = Shop::create([
            'shop' => 'test.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $charge = Charge::create([
            'charge_id' => 11111,
            'shop_id' => '1',
            'name' => 'Basic',
            'price' => 9.99,
            'trial_days' => 15,
            'type' => 'recurring',
            'created_at' => '2016-01-01 00:00:00',
            'billing_on' => '2016-02-01 00:00:00',
            'test' => true,
            'status' => 'active',
            'description' => 'XXX'
        ]);

        $shop->newSubscription($plan)->withCharge($charge)->create();

        $subscription = $shop->subscription();

        $subscription->save();

        $this->assertEquals($charge->id, $subscription->charge->id);
        $this->assertEquals($charge->shop_id, $subscription->charge->shop_id);
    }
}
