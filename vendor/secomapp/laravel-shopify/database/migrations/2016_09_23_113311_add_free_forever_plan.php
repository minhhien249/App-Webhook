<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddFreeForeverPlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('plans')->insert([
            [
                'id' => 1,
                'name' => 'Free Plan',
                'description' => 'Free forever plan',
                'trial_days' => 0,
                'price' => 0,
                'type' => 'recurring'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('plans')->truncate();
    }
}
