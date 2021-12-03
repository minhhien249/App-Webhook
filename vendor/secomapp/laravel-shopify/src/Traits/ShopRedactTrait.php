<?php
/**
 * Created by PhpStorm.
 * User: tiennm
 * Date: 5/25/18
 * Time: 11:15 AM
 */

namespace Secomapp\Traits;


use Secomapp\Events\AppShopRedact;
use Secomapp\Models\Shop;
use Secomapp\Models\ShopInfo;

trait ShopRedactTrait
{
    protected $euCountries = array(
        'AT', 'BE', 'HR', 'BG', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE',
        'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE', 'GB'
    );

    /**
     * @param Shop $shop
     */
    public function redact($shop)
    {
        event(new AppShopRedact($shop));

        $shopInfo = ShopInfo::where('shop_id', $shop->id)->first();
        if ($shopInfo) {
            if (in_array($shopInfo->country_code, $this->euCountries)) {
                $shopInfo->update([
                    'address1'       => null,
                    'address2'       => null,
                    'customer_email' => null,
                    'email'          => null,
                    'phone'          => null,
                    'latitude'       => null,
                    'longitude'      => null,
                    'shop_owner'     => null,
                    'description'    => '',
                ]);
            }
        }
        $shop->clean()->save();
    }
}