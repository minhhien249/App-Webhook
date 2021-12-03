<?php

namespace Secomapp\Contracts;


interface AuthenticateApiContract
{
    /**
     * @param string $shopName
     * @return AuthenticateApiContract
     */
    public function forShopName($shopName);

    /**
     * @param string $state
     * @return mixed
     */
    public function initiateLogin($state);

    /**
     * @param string $code
     * @return string
     */
    public function accessToken($code);

}