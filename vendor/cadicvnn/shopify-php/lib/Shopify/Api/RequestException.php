<?php

namespace Shopify\Api;

class RequestException extends \Exception
{

    /** @var array */
    protected $params;

    /**
     * set the params of the query that cause this exception
     * @param array $params
     */
    public function setQueryParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * get the params of the query that cause this exception
     * @return array
     */
    public function getQueryParams()
    {
        return $this->params;
    }

}
