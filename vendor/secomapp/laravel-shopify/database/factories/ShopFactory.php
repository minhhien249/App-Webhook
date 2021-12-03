<?php

use Secomapp\Models\Shop;

$factory->define(Shop::class, function (Faker\Generator $faker) {
    return [
        'shop' => $faker->name . '.myshopify.com',
        'access_token' => $faker->text(128),
        'installed_at' => $faker->dateTimeBetween('-30 days', '-2 days'),
        'activated_at' => $faker->dateTimeBetween('-1 days'),
        'uninstalled_at' => '0000-00-00 00:00:00'
    ];
});


