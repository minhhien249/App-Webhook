<?php

namespace Secomapp\Traits;


use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Secomapp\Contracts\AuthenticateApiContract;
use Secomapp\Exceptions\ShopifyApiException;
use Secomapp\Models\Shop;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Secomapp\Models\ShopInfo;
use Secomapp\Models\User;
use Secomapp\Pricing;
use \Exception;

trait Authenticator
{
    use InstalledShop;

    public function login($domain, $sudo = false)
    {
        Log::info('login from shopify');
        $shopName = shopNameFromDomain($domain);

        $this->clientApi()->setShopName($shopName);
        /** @var Shop $shop */
        $shop = Shop::findByDomain($domain)->first();
        if (!$shop || $shop->uninstalled()) {
            Log::info('shop install');

            return $this->installApp($shopName);
        }

        $this->clientApi()->setAccessToken($shop->access_token);
        $shopInfo = $this->pullShopInfo($shop);
        if (!$shopInfo) {
            Log::info('shop reinstall without uninstall hook.');

            return $this->installApp($shopName);
        }

        // authenticate
        /** @var User $user */
        $user = User::findByShopId($shop->id)->first();
        $user->updateFromShopInfo($shopInfo)->save();
        auth()->login($user);

        session(['sudo' => $sudo]);

        if (!$sudo) {
            /** @var Pricing $pricing */
            $pricing = app(Pricing::class);
            if (!$pricing->verifyPlan($shop, $this->shopPlan())) {
                return $this->billing();
            }
        }

        return $this->homepage();
    }

    /**
     * Send install app request to Shopify.
     *
     * @param string $shopName
     * @return mixed
     */
    public function installApp($shopName)
    {
        Log::info('install app request');
        try {
            $state = Uuid::uuid4()->toString();
        } catch (Exception $e) {
            $state = date("YmdHis", time());
        }
        session(['state' => $state]);
        try {
            /** @var AuthenticateApiContract $authenticateApi */
            $authenticateApi = app(AuthenticateApiContract::class);

            return $authenticateApi->forShopName($shopName)->initiateLogin($state);
        } catch (RuntimeException $e) {
            Log::error("{$e->getMessage()} {$e->getTraceAsString()}");

            return redirect()->route('add_app')->withInput()->withMessage($e->getMessage());
        }
    }

    /**
     * Update latest ShopInfo from Shopify
     *
     * @param Shop $shop
     * @return ShopInfo | false
     */
    public function pullShopInfo($shop)
    {
        try {
            /** @var \Secomapp\Resources\Shop $shopApi */
            $shopApi = app(\Secomapp\Resources\Shop::class);
            $response = $shopApi->get();
        } catch (ShopifyApiException $e) {
            Log::error("get shop.json failed: {$e->getMessage()} {$e->getTraceAsString()}");

            return false;
        }

        return $this->saveShopInfo($shop, $response);
    }

    /**
     * Update or create shopInfo into database
     *
     * @param Shop   $shop
     * @param object $response
     * @return ShopInfo
     */
    private function saveShopInfo($shop, $response)
    {
        Log::info('save shopinfo.');

        $raw = json_encode($response);
        /** @var ShopInfo $shopInfo */
        try {
            $shopInfo = DB::table('shop_infos')->where('shop_id', $shop->id)->first();
            if (!$shopInfo) {
                DB::table('shop_infos')->insert([
                    'shop_id'     => $shop->id,
                    'shopify_id'  => $response->id,
                    'description' => base64_encode($raw),
                    'created_at'  => Carbon::parse($response->created_at)->toDateTimeString(),
                    'shop_owner'  => $response->shop_owner,
                    'email'       => $response->email,
                    'plan_name'   => $response->plan_name,
                ]);
            }
        } catch (Exception $e) {
            Log::error("can not find shopinfo shop_id={$shop->id}: {$e->getMessage()} {$e->getTraceAsString()}");
        }

        try {
            $si = new ShopInfo();
            $attributes = [];
            foreach ($response as $key => $value) {
                if (!isset ($value)) {
                    continue;
                }

                if ($key === 'created_at' || $key === 'updated_at') {
                    $attributes[$key] = Carbon::parse($value)->toDateTimeString();
                } else if ($key == 'id') {
                    $attributes['shopify_id'] = $value;
                } else {
                    if ($si->isFillable($key)) {
                        $attributes[$key] = $value;
                    } else {
                        Log::warning("new shopinfo field {$key}");
                    }
                }
            }
            $attributes['description'] = base64_encode($raw);

            try {
                DB::table('shop_infos')->where('shop_id', $shop->id)->update($attributes);
            } catch (Exception $e) {
                Log::error("save shopinfo failed: {$e->getMessage()} {$e->getTraceAsString()}"); // todo remove exception handle later.
            }
        } catch (Exception $e) {
            Log::error("read response failed: {$e->getMessage()} {$raw}");
        }

        $shopInfo = DB::table('shop_infos')->where('shop_id', $shop->id)->first();

        return $shopInfo;
    }
}