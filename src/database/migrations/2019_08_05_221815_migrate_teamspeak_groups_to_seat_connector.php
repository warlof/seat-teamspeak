<?php
/**
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2019  Warlof Tutsimo <loic.leuilliot@gmail.com>
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Warlof\Seat\Connector\Models\Set;

/**
 * Class MigrateTeamspeakGroupsToSeatConnector.
 */
class MigrateTeamspeakGroupsToSeatConnector extends Migration
{
    public function up()
    {
        if (Schema::hasTable('teamspeak_groups')) {

            echo 'Converting old teamspeak groups structure to new seat-connector sets scheme...' . PHP_EOL;

            $sets = DB::table('teamspeak_groups')->get();

            foreach ($sets as $set) {

                $connector_set = new Set();
                $connector_set->connector_type = 'teamspeak';
                $connector_set->connector_id   = $set->id;
                $connector_set->name           = $set->name;
                $connector_set->save();

            }
        }
    }
}
