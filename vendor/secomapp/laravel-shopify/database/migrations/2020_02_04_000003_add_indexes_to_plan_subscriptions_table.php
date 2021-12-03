<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToPlanSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->index('shop_id');
            $table->index('plan_id');
            $table->index('charge_id');
            $table->index('trial_ends_at');
            $table->index('starts_at');
            $table->index('ends_at');
            $table->index('cancels_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'plan_id', 'charge_id', 'trial_ends_at', 'starts_at', 'ends_at', 'cancels_at']);
        });
    }
}
