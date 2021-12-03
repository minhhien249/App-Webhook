<?php

namespace Secomapp\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Secomapp\Contracts\ClientApiContract;
use Secomapp\Events\AppLogined;
use Secomapp\Events\AppThemesFetched;
use Secomapp\Models\Shop;
use Secomapp\Pricing;
use Secomapp\Resources\Theme;

trait InstalledShop
{
    /**
     * Return if user login normally.
     *
     * @return bool
     */
    function hasSession()
    {
        return true;
    }

    /**
     * Get Shop
     *
     * @return Shop | false
     */
    function shop()
    {
        if (auth()->check()) {
            return Shop::find($this->shopId());
        } else {
            return false;
        }
    }

    /**
     * Get shop name
     *
     * @return string
     */
    function shopName()
    {
        if (auth()->check()) {
            return auth()->user()->shop_name;
        } else {
            return false;
        }
    }

    /**
     * Get shopPlan
     *
     * @return string
     */
    function shopPlan()
    {
        if (auth()->check()) {
            return auth()->user()->plan_name;
        } else {
            return false;
        }
    }

    /**
     * Get shop id
     *
     * @return string
     */
    function shopId()
    {
        if (auth()->check()) {
            return auth()->user()->shop_id;
        } else {
            return false;
        }
    }

    /**
     * Get ClientApi.
     *
     * @return ClientApiContract
     */
    function clientApi()
    {
        return app(ClientApiContract::class);
    }

    /**
     * Get information about shop themes.
     */
    function pullThemeInfo()
    {
        $themes = false;
        try {
            /** @var Theme $themeApi */
            $themeApi = app(Theme::class);
            $themes = $themeApi->all(['id', 'name', 'role']);
            foreach ($themes as $theme) {
                if ($theme->role == 'main') {
                    session(['theme_id' => $theme->id]);
                    session(['theme_name' => $theme->name]);
                    setting(['theme_id' => $theme->id]);
                } else if ($theme->role == 'mobile') {
                    session(['mobile_theme_id' => $theme->id]);
                    session(['mobile_theme_name' => $theme->name]);
                    setting(['mobile_theme_id' => $theme->id]);
                }
            }
        } catch (\Exception $e) {
            logger("{$this->shopName()}: fetch home fail {$e->getMessage()}, {$e->getTraceAsString()}");
        }

        return $themes;
    }

    public function homepage()
    {

        $themes = $this->pullThemeInfo();

        if ($themes) {
            event(new AppThemesFetched($themes));
        }

        $this->initSession();

        event(new AppLogined());

        return response()->homepage();
    }

    private function initSession()
    {
        session(['has_coupon' => $this->hasAvailableCoupon()]);
    }

    private function hasAvailableCoupon()
    {
        $shopName = $this->shopName();
        $subscription = $this->shop()->subscription();
        if (!$subscription || !$subscription->active()) {
            return false;
        }
        if ($subscription->plan->isFree()) {
            /** @var Pricing $pricing */
            $pricing = app(Pricing::class);
            if (!$pricing->isTestShop($shopName)) {
                return false;
            }
        } else if (!$subscription->charged()) {
            return false;
        }

        /** @var Collection $coupons */
        $coupons = DB::table('coupons')
            ->join('discounts', 'coupons.discount_id', '=', 'discounts.id')
            ->select('coupons.code')
            ->whereRaw("(discounts.plan_id IS NULL OR discounts.plan_id = {$subscription->plan_id})")
            ->whereRaw('(discounts.started_at IS NULL OR discounts.started_at <= NOW())')
            ->whereRaw('(discounts.expired_at IS NULL OR discounts.expired_at >= NOW())')
            ->whereRaw('(discounts.usage_limit IS NULL OR coupons.times_used < discounts.usage_limit)')
            ->whereRaw("(coupons.shop='{$shopName}' or coupons.shop='{$shopName}.myshopify.com')")
            ->where('coupons.status', true)
            ->orderBy('coupons.created_at', 'DESC')
            ->select(['code'])->get();

        if ($coupons->count() === 1) {
            if ($subscription->coupon_code === $coupons->first()->code) {
                // already use coupon code
                return false;
            }
        }

        return $coupons->count() > 0;
    }
}