<?php

namespace Secomapp\Logging;

class ShopNameFormatter
{
    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->pushProcessor(function ($record) {
                if (auth()->check()) {
                    $shopName = auth()->user()->shop_name;
                } else {
                    $domain = request()->input('shop');
                    if (isValidDomain($domain)) {
                        $shopName = shopNameFromDomain($domain);
                    } else {
                        $shopName = $domain;
                    }
                }

                if ($shopName) {
                    $record['message'] = $shopName.': '.$record['message'];
                }

                return $record;
            });
        }
    }
}