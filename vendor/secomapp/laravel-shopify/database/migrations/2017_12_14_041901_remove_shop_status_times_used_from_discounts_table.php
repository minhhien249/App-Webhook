<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveShopStatusTimesUsedFromDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn(['shop', 'times_used', 'status']);
            $table->integer('plan_id')->unsigned()->nullable();

            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->string('shop', 255)->index();
            $table->integer('times_used')->unsigned()->default(0);
            $table->boolean('status')->default(true);

            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
}
