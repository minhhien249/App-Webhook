<?php

namespace Secomapp\Services;


use Illuminate\Support\Facades\Cache;
use Secomapp\Contracts\ShopSettingContract;
use Secomapp\Models\Setting;
use Secomapp\Traits\CacheConfigShopSetting;
use Secomapp\Traits\InstalledShop;

class ShopSettingApi implements ShopSettingContract
{
    use CacheConfigShopSetting, InstalledShop;

    /**
     * Set a setting by key and value
     *
     * @param string $key
     * @param string $value
     * @param string $shopId
     *
     * @return boolean
     */
    public function set($key, $value, $shopId = null)
    {
        $canCache = $shopId == null;
        if (!$shopId) {
            $shopId = $this->shopId();
        }

        $count = Setting::where('shop_id', $shopId)->where('key', $key)->count();
        if ($count > 0) {
            Setting::getQuery()->where('shop_id', $shopId)->where('key', $key)->update([
                'value' => $value,
            ]);
        } else {
            Setting::getQuery()->insert([
                'shop_id' => $shopId,
                'key'     => $key,
                'value'   => $value,
            ]);
        }

        if ($canCache) {
            $this->cacheSet($key, $value, $shopId);
        }

        return $count ? true : false;
    }

    /**
     * Get a setting by key, optionally set a default or fallback to config lookup
     *
     * @param string $key
     * @param string $default
     * @param string $shopId
     *
     * @return string
     */
    public function get($key, $default = null, $shopId = null)
    {
        $canCache = $shopId == null;
        if (!$shopId) {
            $shopId = $this->shopId();
        }

        if ($canCache && $this->cacheHas($key, $shopId)) {
            return $this->cacheGet($key, $shopId);
        }

        $setting = Setting::where('shop_id', $shopId)
            ->where('key', $key)->first();
        if ($setting) {
            $value = $setting->value;
            if ($canCache) {
                $this->cacheSet($key, $value, $shopId);
            }

            return $value;
        }
        if ($this->configHas($key)) {
            $value = $this->configGet($key);
            if ($canCache) {
                $this->cacheSet($key, $value, $shopId);
            }

            return $value;
        }
        if ($default) {
            $value = $default;
            if ($canCache) {
                $this->cacheSet($key, $value, $shopId);
            }

            return $value;
        }

        return false;
    }

    /**
     * Forget a setting by key
     *
     * @param string $key
     * @param string $shopId
     *
     * @return boolean
     */
    public function forget($key, $shopId = null)
    {
        $canCache = $shopId == null;
        if (!$shopId) {
            $shopId = $this->shopId();
        }

        $result = Setting::where('shop_id', $shopId)->where('key', $key)->delete();

        if ($canCache) {
            $this->cacheForget($key, $shopId);
        }

        return $result ? true : false;
    }

    /**
     * Check a setting exists by key
     *
     * @param string $key
     * @param string $shopId
     *
     * @return boolean
     */
    public function has($key, $shopId = null)
    {
        $canCache = $shopId == null;
        if (!$shopId) {
            $shopId = $this->shopId();
        }

        if ($canCache && $this->cacheHas($key, $shopId)) {
            return true;
        }
        $count = Setting::where('shop_id', $shopId)->where('key', $key)->count();
        if ($count > 0) {
            return true;
        }
        if ($this->configHas($key)) {
            return true;
        }

        return false;
    }

    /**
     * Clear all stored settings
     *
     * @param string $shopId
     *
     * @return bool
     */
    public function clear($shopId = null)
    {
        $canCache = $shopId == null;
        if (!$shopId) {
            $shopId = $this->shopId();
        }

        $result = Setting::where('shop_id', $shopId)->delete();
        if ($canCache) {
            Cache::flush();
        }

        return $result;
    }
}