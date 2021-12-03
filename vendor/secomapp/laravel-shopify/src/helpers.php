<?php

if (!function_exists('isValidDomain')) {
    /**
     * Check if the domain is valid shopify domain.
     * Shop domain ends with .shopify.com
     * For example banner-dev.shopify.com
     *
     * @param string $domain shop domain
     * @return boolean
     */
    function isValidDomain($domain)
    {
        return ends_with($domain, '.myshopify.com') && strlen($domain) > 14;
    }
}

if (!function_exists('shopNameFromDomain')) {
    /**
     * Extract shop name from shopify domain by removing .myshopify.com suffix
     *
     * @param string $domain shop domain
     * @return string
     */
    function shopNameFromDomain($domain)
    {
        return substr($domain, 0, -14);
    }
}

if (!function_exists('domainFromShopName')) {
    /**
     * Create shop domain from shopify name by add suffix .myshopify.com
     *
     * @param string $shopName
     * @return string
     */
    function domainFromShopName($shopName)
    {
        return "{$shopName}.myshopify.com";
    }
}

if (!function_exists('isNullTimestamp')) {
    /**
     * Check if timestamp is null or zero time
     *
     * @param $timestamp
     * @return bool
     */
    function isNullTimestamp($timestamp)
    {
        return is_null($timestamp) || $timestamp == '0000-00-00 00:00:00';
    }
}

if (!function_exists('isValidWebhookRequest')) {
    /**
     * Verify if webhook is called from Shopify.
     *
     * @param  $request
     *            request textual content
     *
     * @return boolean true if request is valid
     */
    function isValidWebhookRequest(\Illuminate\Http\Request $request)
    {
        $hmacHeader = $request->header('HTTP_X_SHOPIFY_HMAC_SHA256');
        if (!$hmacHeader || empty ($hmacHeader)) {
            $hmacHeader = $request->server('HTTP_X_SHOPIFY_HMAC_SHA256');
        }

        $calculatedHmac = base64_encode(hash_hmac('sha256', $request->getContent(), config('shopify.shared_secret'), true));

        return $hmacHeader == $calculatedHmac;
    }
}

if (!function_exists('setting')) {
    /**
     * Get / set the specified shop setting value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param array|string $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key = null, $default = null)
    {
        if (is_array($key)) {
            $count = 0;
            foreach ($key as $arrayKey => $arrayValue) {
                $count += \Secomapp\Facades\ShopSetting::set($arrayKey, $arrayValue);
            }
            return $count;
        } else {
            return \Secomapp\Facades\ShopSetting::get($key, $default);
        }
    }
}

if (!function_exists('shopSetting')) {
    function shopSetting($shopId, $key = null, $default = null)
    {
        if (is_array($key)) {
            $count = 0;
            foreach ($key as $arrayKey => $arrayValue) {
                $count += \Secomapp\Facades\ShopSetting::set($arrayKey, $arrayValue, $shopId);
            }
            return $count;
        } else {
            return \Secomapp\Facades\ShopSetting::get($key, $default, $shopId);
        }
    }
}

if (!function_exists('clearChange')) {
    function clearChange($shopId = null)
    {
        if ($shopId) {
            shopSetting($shopId, ['changes' => 0]);
        } else {
            setting(['changes' => 0]);
        }
    }
}

if (!function_exists('increaseChange')) {
    function increaseChange($shopId = null)
    {
        if ($shopId) {
            $changes = shopSetting($shopId, 'changes', 0);
            shopSetting($shopId, ['changes' => $changes + 1]);
        } else {
            $changes = setting('changes', 0);
            setting(['changes' => $changes + 1]);
        }
    }
}

if (!function_exists('jsPack')) {
    /**
     *
     * @param string $script the JavaScript to pack.
     * @return string
     */
    function jsPack($script)
    {
        $packer = new JavaScriptPacker($script, 62, true, false);
        return $packer->pack();
    }
}


if (!function_exists('cssPacker')) {
    /**
     * Minify css with given $css
     *
     * @param string $css
     * @return string
     */
    function cssPacker($css)
    {
        $compressor = new \tubalmartin\CssMin\Minifier();
        $compressor->removeImportantComments();
        $compressor->setLineBreakPosition(1000);
        $compressor->setMemoryLimit('256M');
        $compressor->setMaxExecutionTime(120);
        $compressor->setPcreBacktrackLimit(3000000);
        $compressor->setPcreRecursionLimit(150000);
        return $compressor->run($css);
    }
}


if (!function_exists('htmlPacker')) {

    /**
     * Minify HTML with given $html
     *
     * @param string $html
     * @param bool $decodeUtf8Specials
     * @return string
     */
    function htmlPacker($html, $decodeUtf8Specials = false)
    {
        $htmlPacker = new \voku\helper\HtmlMin();
        $htmlPacker->doOptimizeViaHtmlDomParser();
        $htmlPacker->doRemoveComments();
        $htmlPacker->doSumUpWhitespace();
        $htmlPacker->doRemoveWhitespaceAroundTags();
        $htmlPacker->doOptimizeAttributes();
        $htmlPacker->doRemoveHttpPrefixFromAttributes();
        $htmlPacker->doRemoveDefaultAttributes();
        $htmlPacker->doRemoveDeprecatedAnchorName();
        $htmlPacker->doRemoveDeprecatedScriptCharsetAttribute();
        $htmlPacker->doRemoveDeprecatedTypeFromScriptTag();
        $htmlPacker->doRemoveDeprecatedTypeFromStylesheetLink();
        $htmlPacker->doRemoveEmptyAttributes();
        $htmlPacker->doRemoveValueFromEmptyInput();
        $htmlPacker->doSortCssClassNames();
        $htmlPacker->doSortHtmlAttributes();
        $htmlPacker->doRemoveSpacesBetweenTags();
        $htmlPacker->doRemoveOmittedQuotes();
        $htmlPacker->doRemoveOmittedHtmlTags();
        return $htmlPacker->minify($html, $decodeUtf8Specials);
    }
}

if (!function_exists('getSessionOrSetting')) {
    /**
     * Get value from session or setting by key
     *
     * @param string $key
     * @param null|mixed $default
     * @return mixed
     */
    function getSessionOrSetting($key, $default = null)
    {
        if (session()->has($key)) {
            return session()->get($key, $default);
        }
        return setting($key, $default);
    }
}

if (!function_exists('setSessionAndSetting')) {

    /**
     * Set both of setting and session for application
     *
     * @param string $key
     * @param mixed $value
     */
    function setSessionAndSetting($key, $value)
    {
        session($key, $value);
        setting($key, $value);
    }
}

if (!function_exists('removeSessionAndSetting')) {

    /**
     * Remove a key from setting and session via $key
     *
     * @param string $key
     */
    function removeSessionAndSetting($key)
    {
        if (\Secomapp\Facades\ShopSetting::has($key)) {
            \Secomapp\Facades\ShopSetting::clear($key);
        }
        if (session()->has($key)) {
            session()->remove($key);
        }
    }
}