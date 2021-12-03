<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndexesToShopInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shop_infos', function (Blueprint $table) {
            $table->index('country_code');
            $table->index('currency');
            $table->index('plan_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shop_infos', function (Blueprint $table) {
            $table->dropIndex(['country_code', 'currency', 'plan_name']);
        });
    }
}
