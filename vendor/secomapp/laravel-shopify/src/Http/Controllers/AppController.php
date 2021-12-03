<?php

namespace Secomapp\Http\Controllers;


use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Secomapp\Contracts\AuthenticateApiContract;
use Secomapp\Events\AppInstalled;
use Secomapp\Events\AppUninstalled;
use Secomapp\Exceptions\ShopifyApiException;
use Secomapp\Http\Requests\AddAppRequest;
use Secomapp\Models\Shop;
use Secomapp\Models\User;
use Secomapp\Resources\Webhook;
use Secomapp\Traits\Authenticator;
use Secomapp\Traits\ChargeCreator;
use Secomapp\Traits\InstalledShop;

class AppController extends Controller
{
    use InstalledShop, ChargeCreator, Authenticator {
        InstalledShop::clientApi insteadof ChargeCreator;
        InstalledShop::clientApi insteadof Authenticator;
        InstalledShop::shop insteadof ChargeCreator;
        InstalledShop::shop insteadof Authenticator;
        InstalledShop::shopPlan insteadof ChargeCreator;
        InstalledShop::shopPlan insteadof Authenticator;
        InstalledShop::shopId insteadof ChargeCreator;
        InstalledShop::shopId insteadof Authenticator;
        InstalledShop::shopName insteadof ChargeCreator;
        InstalledShop::shopName insteadof Authenticator;
        InstalledShop::pullThemeInfo insteadof ChargeCreator;
        InstalledShop::pullThemeInfo insteadof Authenticator;
        InstalledShop::homepage insteadof ChargeCreator;
        InstalledShop::homepage insteadof Authenticator;
        InstalledShop::hasSession insteadof ChargeCreator;
        InstalledShop::hasSession insteadof Authenticator;
        InstalledShop::initSession insteadof ChargeCreator;
        InstalledShop::initSession insteadof Authenticator;
        InstalledShop::hasAvailableCoupon insteadof ChargeCreator;
        InstalledShop::hasAvailableCoupon insteadof Authenticator;
    }

    /**
     * Show add_app page.
     * GET /add_app
     *
     * @param $request
     * @return View
     */
    public function getAddApp(Request $request)
    {
        $shop = $request->input('shop') ?: '';
        $couponCode = $request->input('coupon_code') ?: '';

        return view('laravel-shopify::install', compact('shop', 'couponCode'));
    }

    /**
     * Authenticate with Shopify.
     * POST /add_app
     *
     * This will redirect your user to a Shopify login screen
     * where they will need to authenticate with their Shopify credentials
     *
     * @param $request
     * @return Response
     */
    public function postAddApp(AddAppRequest $request)
    {
        $shopName = shopNameFromDomain($request->input('shop'));
        $couponCode = $request->input('coupon_code') ?: '';

        Log::info("add app {$couponCode}");

        if ($couponCode) {
            session(['coupon_code' => $couponCode]);
        } else {
            Session::forget('coupon_code');
        }

        return $this->installApp($shopName);
    }

    /**
     * Login from Shopify
     * GET /shopify
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function loginShopify(Request $request)
    {
        $domain = $request->input('shop');

        return $this->login($domain);
    }

    /**
     * Login from Shopify
     * GET /shopify
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json([
            'affected' => 1,
        ]);
    }

    /**
     * Confirmation of app install
     * GET /authorize
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function authorizeApp(Request $request)
    {
        Log::info('authorize from shopify');
        $domain = $request->input('shop');
        $shopName = shopNameFromDomain($domain);

        $this->clientApi()->setShopName($shopName);
        /** @var Shop $shop */
        $shop = Shop::findByDomain($domain)->first();

        // check if authorize is called twice
        if ($this->installedApp($shop)) {
            Log::info('shop already installed');

            return $this->login($domain);
        }

        /** @var AuthenticateApiContract $authenticateApi */
        $authenticateApi = app(AuthenticateApiContract::class);
        $accessToken = $authenticateApi->forShopName($shopName)->accessToken($request->input('code'));
        if (!$accessToken) {
            Log::warning('request access token failed');

            return $this->installApp($shopName);
        }

        if ($shop) {
            // shop is reinstalled & shop uninstalled hook is not yet run
            if (!$shop->uninstalled()) {
                $this->uninstallApp($shop, false);
            }
            $shop->install($accessToken)->save();
        } else {
            $shop = Shop::create([
                'shop'         => $domain,
                'access_token' => $accessToken,
                'installed_at' => new Carbon(),
            ]);
        }

        $this->clientApi()->setAccessToken($accessToken);

        $user = $this->findOrCreateUser($shop);
        $shopInfo = $this->pullShopInfo($shop);
        $user->updateFromShopInfo($shopInfo)->save();
        auth()->login($user);

        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'shopify', 'shop' => $shopName])
                ->log('app installed');
        }

        try {
            /** @var Webhook $webhookApi */
            $webhookApi = app(Webhook::class);
            $webhookApi->create('app/uninstalled', route('uninstalled_webhook'));
        } catch (ShopifyApiException $e) {
            Log::warning("setup uninstalled webhook failed: {$e->getTraceAsString()}");
        }

        event(new AppInstalled($shop));

        if (!session('sudo', false)) {
            activity()->causedBy(auth()->user())->withProperties(['layer' => 'app', 'shop' => $shopName])
                ->log('app installed');
        }

        return $this->billing();
    }

    /**
     * Uninstalled app called from Shopify webhook.
     * GET /uninstalled
     *
     * @param $request
     */
    public function uninstalledWebhook(Request $request)
    {
        $content = $request->getContent();
        $domain = json_decode($content)->myshopify_domain;
        $shopName = shopNameFromDomain($domain);
        Log::info("{$shopName}: uninstalled webhook");

        $shop = Shop::findByDomain($domain)->first();
        if (!$shop) {
            Log::warning("{$shopName}: uninstalled webhook can't find shop with content: {$content}");

            return;
        }

        $this->uninstallApp($shop);
    }

    /**
     * Check if app is already installed.
     *
     * @param Shop $shop
     * @return bool
     */
    private function installedApp($shop)
    {
        if (!$shop || $shop->uninstalled()) {
            return false;
        }

        $this->clientApi()->setAccessToken($shop->access_token);
        try {
            /** @var \Secomapp\Resources\Shop $shopApi */
            $shopApi = app(\Secomapp\Resources\Shop::class);
            $shopApi->get();
        } catch (ShopifyApiException $e) {
            return false;
        }

        return true;
    }

    /**
     * Update info when the shop is uninstalled.
     *
     * @param Shop $shop
     * @param bool $isHook
     */
    private function uninstallApp(Shop $shop, $isHook = true)
    {
        $shopName = shopNameFromDomain($shop->shop);

        if ($isHook) {
            Log::info("{$shopName}: uninstall previous install");
        } else {
            Log::info('uninstall previous install');
        }
        $shop->uninstall()->deactivate()->save();

        if (!$isHook) {
            $this->forgetSession();
        }

        event(new AppUninstalled($shop->id, $isHook));

        if ($isHook) {
            if (!session('sudo', false)) {
                activity()->withProperties(['layer' => 'app', 'shop' => $shopName])
                    ->log('app uninstalled');
            }
        }
    }

    private function forgetSession()
    {
        Session::forget('charge_plan_id');
        Session::forget('charge_coupon_code');
        Session::forget('charge_type');
        Session::forget('charge_shop_plan');
        Session::forget('state');
        Session::forget('verify_plan');
    }

    /**
     * Return user if exists; create and return if does not
     *
     * @param Shop $shop
     * @return User
     */
    private function findOrCreateUser($shop)
    {
        return User::firstOrCreate([
            'shop_id'   => $shop->id,
            'shop_name' => shopNameFromDomain($shop->shop),
        ]);
    }

    /**
     * @return View
     */
    public function welcome()
    {
        return view('laravel-shopify::welcome');
    }

    /**
     * The entry for app after login or charge
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function dashboard()
    {
        return redirect('/');
    }

    public function getMessage()
    {
        return view('laravel-shopify::message');
    }
}

