<?php

namespace Secomapp\Tests\Unit;


use Secomapp\Models\User;
use Illuminate\Support\Facades\Auth;
use Secomapp\Facades\ShopSetting;
use Secomapp\Models\Setting;
use Secomapp\Models\Shop;
use Secomapp\Tests\TestCase;

class SettingTest extends TestCase
{
    /**
     * Can return all configured feature codes.
     *
     * @test
     * @return void
     */
    public function it_can_save_and_get_setting()
    {
        $shop = Shop::create([
            'shop' => 'test.myshopify.com',
            'access_token' => 'abcxyz',
            'installed_at' => '2016-01-01 00:00:00',
            'activated_at' => '0000-00-00 00:00:00',
            'uninstalled_at' => '0000-00-00 00:00:00',
            'used_days' => 0
        ]);

        $setting = Setting::create([
            'shop_id' => $shop->id,
            'key' => 'abc',
            'value' => 'xyz'
        ]);

        $value = ShopSetting::get('abc', null, $shop->id);

        $this->assertEquals($setting->value, $value);

        $user = new User;
        $user->shop_id = $shop->id;

        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('check')->andReturn(true);

        $value = ShopSetting::get('abc');
        $this->assertEquals($setting->value, $value);

        $user->shop_id = $shop->id + 1;

        $value = ShopSetting::get('abc');
        $this->assertFalse($value);

        $user->shop_id = $shop->id;
        ShopSetting::set('abc', 'xyz2');

        $this->assertEquals('xyz2', ShopSetting::get('abc'));

        ShopSetting::forget('abc');

        $this->assertFalse(ShopSetting::get('abc'));
    }
}