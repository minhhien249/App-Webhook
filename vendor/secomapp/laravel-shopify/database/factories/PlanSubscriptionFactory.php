<?php

use Secomapp\Models\Plan;
use Secomapp\Models\Shop;
use Secomapp\Models\PlanSubscription;

$factory->define(PlanSubscription::class, function (Faker\Generator $faker) {
    return [
        'shop_id' => factory(Shop::class)->create()->id,
        'plan_id' => factory(Plan::class)->create()->id,
        'name' => $faker->word
    ];
});