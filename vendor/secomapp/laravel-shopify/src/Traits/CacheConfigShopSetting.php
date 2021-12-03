<?php

namespace Secomapp\Traits;


use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

trait CacheConfigShopSetting
{
    static $CACHE_TAG = 'setting::';
    static $CONFIG_TAG = 'setting.';

    /**
     * Return a key with an attached cache tag
     *
     * @param string $key
     * @param string $shopId
     *
     * @return string
     */
    private function attachCacheTag($key, $shopId)
    {
        return self::$CACHE_TAG."{$shopId}::{$key}";
    }

    /**
     * Return a key with an attached config tag
     *
     * @param string $key
     *
     * @return string
     */
    private function attachConfigTag($key)
    {
        return self::$CONFIG_TAG.$key;
    }

    /**
     * Check a setting exists in cache
     *
     * @param string $key
     * @param string $shopId
     *
     * @return boolean
     */
    public function cacheHas($key, $shopId)
    {
        return Cache::has($this->attachCacheTag($key, $shopId)) ? true : false;
    }

    /**
     * Check a setting exists in cache
     *
     * @param string $key
     * @param mixed  $value
     * @param string $shopId
     *
     */
    public function cacheSet($key, $value, $shopId)
    {
        $expiresAt = Carbon::now()->addMinutes(Config::get('setting.cache_time', 10));
        Cache::put($this->attachCacheTag($key, $shopId), $value, $expiresAt);
    }

    /**
     * remove a setting from cache
     *
     * @param string $key
     * @param string $shopId
     *
     */
    public function cacheForget($key, $shopId)
    {
        Cache::forget($this->attachCacheTag($key, $shopId));
    }

    /**
     * Check a setting exists in config
     *
     * @param string $key
     *
     * @return boolean
     */
    public function configHas($key)
    {
        return Config::get($this->attachConfigTag($key)) ? true : false;
    }

    /**
     * Return cache values
     *
     * @param string $key
     * @param string $shopId
     *
     * @return string array
     */
    protected function cacheGet($key, $shopId)
    {
        return Cache::get($this->attachCacheTag($key, $shopId));
    }

    /**
     * Return config values
     *
     * @param string $key
     *
     * @return string array
     */
    protected function configGet($key)
    {
        return Config::get($this->attachConfigTag($key));
    }
}