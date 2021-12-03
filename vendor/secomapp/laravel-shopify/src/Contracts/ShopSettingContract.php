<?php

namespace Secomapp\Contracts;


interface ShopSettingContract
{
    /**
     * Set a setting by key and value
     *
     * @param string $key
     * @param string $value
     * @param string $shopId
     *
     * @return boolean
     */
    public function set($key, $value, $shopId = null);

    /**
     * Get a setting by key, optionally set a default or fallback to config lookup
     *
     * @param string $key
     * @param string $default
     * @param string $shopId
     *
     * @return string
     */
    public function get($key, $default = null, $shopId = null);

    /**
     * Forget a setting by key
     *
     * @param string $key
     * @param string $shopId
     *
     * @return boolean
     */
    public function forget($key, $shopId = null);

    /**
     * Check a setting exists by key
     *
     * @param string $key
     * @param string $shopId
     *
     * @return boolean
     */
    public function has($key, $shopId = null);

    /**
     * Clear all stored settings
     *
     * @param string $shopId
     *
     * @return boolean
     */
    public function clear($shopId = null);
}