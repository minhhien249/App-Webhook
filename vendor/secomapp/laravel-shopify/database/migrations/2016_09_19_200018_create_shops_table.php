<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShopsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('shop', 255)->unique()->index();
            $table->string('access_token', 255)->nullable();
            $table->timestamp('installed_at')->useCurrent();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->integer('used_days')->unsigned()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
