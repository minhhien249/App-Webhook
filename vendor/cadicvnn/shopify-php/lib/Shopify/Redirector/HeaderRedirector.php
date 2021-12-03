<?php

namespace Shopify\Redirector;

class HeaderRedirector implements \Shopify\Redirector
{

    public function redirect($uri)
    {

        header('Location: ' . $uri);
        exit(0);

    }

}
