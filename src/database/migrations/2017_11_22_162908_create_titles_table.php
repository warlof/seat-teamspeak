<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTitlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teamspeak_group_titles', function (Blueprint $table) {
            $table->integer('corporation_id');
            $table->integer('title_id');
            $table->integer('title_surrogate_key');
            $table->string('tsgrp_id');
            $table->boolean('enable')->default(true);
            $table->timestamps();

            $table->primary(['corporation_id', 'title_id', 'tsgrp_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('teamspeak_group_titles');
    }
}
