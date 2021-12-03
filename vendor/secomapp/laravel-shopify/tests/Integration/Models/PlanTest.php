<?php

namespace Secomapp\Tests\Integration\Models;


use Secomapp\Models\Plan;
use Secomapp\Models\PlanFeature;
use Secomapp\Tests\TestCase;

class PlanTest extends TestCase
{

    /**
     * Can create a plan with features attached.
     *
     * @test
     * @return void
     */
    public function it_can_create_a_plan_and_attach_features_to_it()
    {
        $plan = Plan::create([
            'name' => 'Pro',
            'description' => 'Pro plan',
            'price' => 9.99,
            'type' => 'recurring',
            'trial_days' => 15,
        ]);

        $plan->features()->saveMany([
            new PlanFeature(['code' => 'listings_per_month', 'value' => 50]),
            new PlanFeature(['code' => 'pictures_per_listing', 'value' => 10]),
            new PlanFeature(['code' => 'listing_duration_days', 'value' => 30]),
        ]);

        $plan->fresh();

        $this->assertEquals('Pro', $plan->name);
        $this->assertEquals(3, $plan->features->count());
    }

    /**
     * Check if plan is free or not.
     *
     * @test
     * @return void
     */
    public function it_can_check_if_plan_is_free_or_not()
    {
        $free = new Plan([
            'price' => 0.00
        ]);

        $notFree = new Plan([
            'price' => 9.99
        ]);

        $this->assertTrue($free->isFree());
        $this->assertFalse($notFree->isFree());
    }

    /**
     * Check if plan is has trial.
     *
     * @test
     * @return void
     */
    public function it_has_trial()
    {
        $withoutTrial = new Plan([
            'trial_days' => 0
        ]);

        $withTrial = new Plan([
            'trial_days' => 5
        ]);

        $this->assertTrue($withTrial->hasTrial());
        $this->assertFalse($withoutTrial->hasTrial());
    }
}
