<?php

namespace Secomapp\Events;


class AppUninstalled
{
    private $shopId;
    private $isHook;

    /**
     * AppUninstalled constructor.
     * @param string $shopId
     * @param boolean $isHook
     */
    public function __construct($shopId, $isHook)
    {
        $this->shopId = $shopId;
        $this->isHook = $isHook;
    }

    /**
     * @return string
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @return boolean
     */
    public function isIsHook()
    {
        return $this->isHook;
    }
}