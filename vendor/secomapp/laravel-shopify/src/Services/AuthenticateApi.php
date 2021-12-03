<?php

namespace Secomapp\Services;


use Secomapp\Contracts\AuthenticateApiContract;
use Shopify\Api\AuthenticationGateway;
use Shopify\HttpClient\CurlHttpClient;

class AuthenticateApi implements AuthenticateApiContract
{

    /**
     * @var AuthenticationGateway
     */
    private $authenticate;

    function __construct()
    {
        $httpClient = new CurlHttpClient();
        $httpClient->setVerifyPeer(false);
        $authenticate = new AuthenticationGateway ($httpClient, new LaravelRedirector ());
        $authenticate->usingClientId(config('shopify.api_key'))
            ->usingClientSecret(config('shopify.shared_secret'))
            ->withScope(config('shopify.permissions'))
            ->andReturningTo(config('shopify.redirect_url'));
        $this->authenticate = $authenticate;
    }

    /**
     * @param string $shopName
     * @return AuthenticateApi
     */
    public function forShopName($shopName)
    {
        $this->authenticate->forShopName($shopName);

        return $this;
    }

    /**
     * initiate the login process
     * @param string $state
     * @return mixed
     */
    public function initiateLogin($state)
    {
        return $this->authenticate->initiateLogin($state);
    }

    /**
     * Exchange the token
     *
     * @param $code
     * @return string
     */
    public function accessToken($code)
    {
        return $this->authenticate->toExchange($code);
    }

}