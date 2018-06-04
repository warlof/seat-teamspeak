<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateTeamspeakTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teamspeak_groups', function (Blueprint $table) {
            $table->string('id');
            $table->string('name');
            $table->boolean('is_server_group')->default(true);
            $table->timestamps();
            
            $table->primary('id');
        });

        Schema::create('teamspeak_users', function (Blueprint $table) {
            $table->bigInteger('user_id');
            $table->string('teamspeak_id');
            $table->timestamps();

            $table->primary('user_id');
        });

        Schema::table('teamspeak_users', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_alliances', function (Blueprint $table) {
            $table->integer('alliance_id');
            $table->string('group_id');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary(['alliance_id', 'group_id']);

            $table->foreign('group_id')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');

        });

        Schema::create('teamspeak_group_corporations', function (Blueprint $table) {
            $table->integer('corporation_id');
            $table->string('group_id');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary(['corporation_id', 'group_id']);

            $table->foreign('group_id')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_roles', function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->string('group_id');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary(['role_id', 'group_id']);

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('group_id')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_users', function (Blueprint $table) {
            $table->bigInteger('user_id');
            $table->string('group_id');
            $table->boolean('enable')->default(true);
            $table->timestamps();

            $table->primary(['user_id', 'group_id']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('group_id')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_public', function (Blueprint $table) {
            $table->string('group_id');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary('group_id');

            $table->foreign('group_id')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_logs', function (Blueprint $table) {
            $table->increments('id');

            $table->string('event');
            $table->string('message');
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
        Schema::drop('teamspeak_group_alliances');
        Schema::drop('teamspeak_group_corporations');
        Schema::drop('teamspeak_group_roles');
        Schema::drop('teamspeak_group_users');
        Schema::drop('teamspeak_group_public');
        Schema::drop('teamspeak_users');
        Schema::drop('teamspeak_group');
        Schema::drop('teamspeak_logs');
    }
}
