<?php
return [
    'api_key' => env('SHOPIFY_API_KEY'),
    'shared_secret' => env('SHOPIFY_SHARED_SECRET'),
    'redirect_url' => env('SHOPIFY_REDIRECT_URL'),
    'permissions' => [
        env('SHOPIFY_PERMISSIONS')
    ],
    'admin_shop_names' => explode(',', env('ADMIN_SHOP_NAMES')),
    'remove_days' => 30,
    'remove_attributes' => [
        'address1' => null,
        'address2' => null,
        'customer_email' => null,
        'email' => null,
        'phone' => null,
        'latitude' => null,
        'longitude' => null,
        'shop_owner' => null,
        'description' => '',
    ]
];