<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 255);
            $table->string('description', 512)->nullable();
            $table->decimal('price', 8, 2)
                ->unsigned()->default('0.00');
            $table->integer('trial_days')
                ->unsigned()->default(0);
            $table->enum('type', ['recurring', 'one-time'])
                ->default('recurring');
            $table->boolean('status')->default(true);
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
        Schema::dropIfExists('plans');
    }
}
