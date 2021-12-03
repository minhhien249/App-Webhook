<?php

namespace Shopify\HttpClient;

use Shopify\HttpClient;

abstract class HttpClientAdapter implements HttpClient
{

    const SHOPIFY_ACCESS_TOKEN_HEADER = 'X-Shopify-Access-Token';

    /** @var string */
    protected $accessToken;

    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * get the Shopify permanent access token
     * @return string
     */
    protected function getAccessToken()
    {
        return $this->accessToken;
    }


}
