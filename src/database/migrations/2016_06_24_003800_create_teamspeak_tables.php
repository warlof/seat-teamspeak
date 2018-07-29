<?php

/**
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2018  Warlof Tutsimo <loic.leuilliot@gmail.com>
 *
 * SeAT Teamspeak Connector  is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * SeAT Teamspeak Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
            $table->unsignedInteger('group_id');
            $table->string('teamspeak_id');
            $table->timestamps();

            $table->primary('group_id');

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_alliances', function (Blueprint $table) {
            $table->integer('alliance_id');
            $table->string('teamspeak_sgid');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary(['alliance_id', 'teamspeak_sgid']);

            $table->foreign('teamspeak_sgid')
                ->references('id', 'teamspeak_group_alliances_fk_groups')
                ->on('teamspeak_groups')
                ->onDelete('cascade');

            $table->foreign('alliance_id')
                ->references('alliance_id')
                ->on('alliances')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_corporations', function (Blueprint $table) {
            $table->bigInteger('corporation_id');
            $table->string('teamspeak_sgid');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary(['corporation_id', 'teamspeak_sgid'], 'teamspeak_group_corporations_pk');

            $table->foreign('teamspeak_sgid')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');

            $table->foreign('corporation_id')
                ->references('corporation_id')
                ->on('corporation_infos')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_roles', function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->string('teamspeak_sgid');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary(['role_id', 'teamspeak_sgid']);

            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');

            $table->foreign('teamspeak_sgid')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_users', function (Blueprint $table) {
            $table->unsignedInteger('group_id');
            $table->string('teamspeak_sgid');
            $table->boolean('enable')->default(true);
            $table->timestamps();

            $table->primary(['group_id', 'teamspeak_sgid']);

            $table->foreign('group_id')
                ->references('id')
                ->on('groups')
                ->onDelete('cascade');

            $table->foreign('teamspeak_sgid')
                ->references('id')
                ->on('teamspeak_groups')
                ->onDelete('cascade');
        });

        Schema::create('teamspeak_group_public', function (Blueprint $table) {
            $table->string('teamspeak_sgid');
            $table->boolean('enable')->default(true);;
            $table->timestamps();

            $table->primary('teamspeak_sgid');

            $table->foreign('teamspeak_sgid')
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
