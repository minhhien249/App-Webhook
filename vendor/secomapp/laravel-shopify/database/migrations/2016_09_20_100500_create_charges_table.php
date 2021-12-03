<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateChargesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('charge_id')->unsigned()->index();
            $table->integer('shop_id')->unsigned()->index();
            $table->string('name');
            $table->decimal('price', 8, 2)->unsigned();
            $table->integer('trial_days')->unsigned();
            $table->enum('type', ['recurring', 'one-time']);
            $table->string('status');
            $table->timestamp('billing_on')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('trial_ends_on')->nullable();
            $table->timestamp('cancelled_on')->nullable();
            $table->boolean('test');
            $table->text('description');

            $table->foreign('shop_id')->references('id')->on('shops')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charges');
    }
}
