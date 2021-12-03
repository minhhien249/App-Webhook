<?php

return [

    'free_forever' => true,
    /*
    |--------------------------------------------------------------------------
    | Positive Words
    |--------------------------------------------------------------------------
    |
    | These words indicates "true" and are used to check if a particular plan
    | feature is enabled.
    |
    */
    'positive_words' => [
        'Y',
        'YES',
        'TRUE',
        'UNLIMITED',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | The heart of this package. Here you will specify all features available
    | for your plans.
    |
    */
    'features' => [
        'SAMPLE_SIMPLE_FEATURE',
        'SAMPLE_DEFINED_FEATURE' => [
            'reseteable_interval' => 'month',
            'reseteable_count' => 2
        ],
    ],

    'plan_map' => [
        'shopify_plus'          => 14,
        'unlimited'             => 13,
        'singtel_unlimited'     => 13,
        'uafrica_unlimited'     => 13,
        'professional'          => 12,
        'singtel_professional'  => 12,
        'uafrica_professional'  => 12,
        'business'              => 11,
        'basic'                 => 11,
        'singtel_basic'         => 11,
        'uafrica_basic'         => 11,
        'starter'               => 11,
        'singtel_starter'       => 11,
        'uafrica_starter'       => 11,
        'trial'                 => 11,
        'singtel_trial'         => 11,
        'uafrica_trial'         => 11,
        'staff'                 => 11,
        'custom'                => 11,
        'dormant'               => 11,
        'grandfather'           => 11,
        'npo_full'              => 11,
        'wix_lite'              => 11,
        'npo_lite'              => 11,
        'affiliate'             => -1
    ],
    'plan_shopify_plan_map' => [
        10  => 'Basic',
        11  => 'Basic',
        12  => 'Professional',
        13  => 'Unlimited',
        14  => 'Plus'
    ],
    'basic_plan_id' => 11,
    'free_plan_id' => 11,
    'free_basic_plan_id' => 10,
    'free_basic_no_order_threshold' => 10,
    'test' => env('SHOPIFY_TEST', false),
    'dev_shop_names' => env('SHOPIFY_DEV_SHOPS_NAMES', '')
];
