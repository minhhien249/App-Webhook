<?php

namespace Secomapp\Tests\Integration\Models;


use Secomapp\Models\Shop;
use Secomapp\Tests\TestCase;
use Illuminate\Support\Facades\Config;

class ShopTest extends TestCase
{

    /**
     * Can create a shop.
     *
     * @test
     * @return void
     */
    public function it_can_create_a_shop()
    {
        $shop = Shop::create([
            'shop' => 'test.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $this->assertEquals('test.myshopify.com', $shop->shop);
    }

    /**
     * Test if we can modified shop on the fly and don't need to update to session or not
     *
     * @test
     * @return void
     */
    public function session_shop()
    {
        Config::set('session.driver', 'file');

        $shop = Shop::create([
            'shop' => 'test.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        session(["shop" => $shop]);

        $shop->shop = 'test2.myshopify.com';
        $sessionShop = session("shop");

        $sessionShop->shop = 'test3.myshopify.com';

        $sessionShop2 = session("shop");

        $this->assertEquals($shop->shop, $sessionShop->shop);
        $this->assertEquals($shop->shop, $sessionShop2->shop);
    }
}
