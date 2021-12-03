<?php

namespace Secomapp\Services;


use Shopify\Redirector;

/**
 * Redirect by laravel.
 *
 */
class LaravelRedirector implements Redirector
{
    public function redirect($uri)
    {
        return redirect($uri);
    }
}
