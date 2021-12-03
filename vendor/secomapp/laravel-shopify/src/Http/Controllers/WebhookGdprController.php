<?php

namespace Secomapp\Http\Controllers;

use Secomapp\Jobs\WebhookGdprJob;
use Illuminate\Http\Request;

/**
 * @see     https://help.shopify.com/api/guides/gdpr-resources
 *
 * @package Secomapp\Http\Controllers
 */
class WebhookGdprController extends Controller
{
    /**
     * @param $request
     * @return string
     */
    public function getCustomersRedact(Request $request)
    {
        $content = $this->getContent($request);
        $shopName = shopNameFromDomain($content->shop_domain);

        $this->dispatch(new WebhookGdprJob($shopName, 'customersRedact', $content));

        return 'ok';
    }

    /**
     * @param $request
     * @return string
     */
    public function getShopRedact(Request $request)
    {
        $content = $this->getContent($request);
        $shopName = shopNameFromDomain($content->shop_domain);

        $this->dispatch(new WebhookGdprJob($shopName, 'shopRedact', $content));

        return 'ok';
    }

    private function getContent(Request $request)
    {
        return json_decode($request->getContent());
    }
}
