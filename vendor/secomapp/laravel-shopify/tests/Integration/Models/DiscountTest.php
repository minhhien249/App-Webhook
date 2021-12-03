<?php

namespace Secomapp\Tests\Integration\Models;

use Secomapp\Models\Discount;
use Secomapp\Tests\TestCase;

class DiscountTest extends TestCase
{

    /**
     * Can create a discount and find it.
     *
     * @test
     * @return void
     */
    public function find_by_shop()
    {
        $discount = Discount::create([
            'shop' => 'test.myshopify.com',
            'name' => 'ABC',
            'type' => 'amount',
            'value' => 50,
            'status' => 1
        ]);

        $discounts = Discount::findByDomain('test.myshopify.com')->get();
        $this->assertEquals(1, $discounts->count());
    }

    /**
     * Can create a discount and find it.
     *
     * @test
     * @return void
     */
    public function find_by_shop_2()
    {
        $discount = Discount::create([
            'shop' => 'test.myshopify.com',
            'name' => 'ABC',
            'type' => 'amount',
            'value' => 50,
            'status' => 1
        ]);

        $discount = Discount::create([
            'shop' => 'test.myshopify.com',
            'name' => 'ABC',
            'type' => 'amount',
            'value' => 50,
            'status' => 0
        ]);

        $discount = Discount::create([
            'shop' => 'test.myshopify.com',
            'name' => 'ABC',
            'type' => 'amount',
            'usage_limit' => 1,
            'times_used' => 1,
            'value' => 50,
            'status' => 1
        ]);
        $discount = Discount::create([
            'shop' => 'test.myshopify.com',
            'name' => 'ABC',
            'expired_at' => '2010-01-01 00:00:00',
            'type' => 'amount',
            'value' => 50,
            'status' => 1
        ]);
        $discount = Discount::create([
            'shop' => 'test.myshopify.com',
            'name' => 'ABC',
            'started_at' => '3010-01-01 00:00:00',
            'type' => 'amount',
            'value' => 50,
            'status' => 1
        ]);

        $discounts = Discount::findByDomain('test.myshopify.com')->get();
        $this->assertEquals(1, $discounts->count());
    }
}
