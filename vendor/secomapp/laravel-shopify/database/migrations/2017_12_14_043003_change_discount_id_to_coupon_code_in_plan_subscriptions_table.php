<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDiscountIdToCouponCodeInPlanSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plan_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropColumn('discount_id');
            $table->string('coupon_code', 128)->nullable()->after('plan_id')->references('code')->on('coupons');
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
            $table->dropForeign(['coupon_code']);
            $table->dropColumn('coupon_code');
            $table->integer('discount_id')->after('plan_id')->unsigned()->nullable();
            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
        });
    }
}
