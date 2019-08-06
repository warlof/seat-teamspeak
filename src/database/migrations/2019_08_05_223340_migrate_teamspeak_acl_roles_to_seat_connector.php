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
 * Class MigrateTeamspeakAclRolesToSeatConnector.
 */
class MigrateTeamspeakAclRolesToSeatConnector extends Migration
{
    public function up()
    {
        if (Schema::hasTable('teamspeak_group_roles')) {

            echo 'Converting old teamspeak roles policy structure to new seat-connector policies scheme...' . PHP_EOL;

            $policies = DB::table('teamspeak_group_roles')->get();

            foreach ($policies as $policy) {

                $connector_set = Set::where('connector_type', 'teamspeak')
                    ->where('connector_id', $policy->teamspeak_sgid)
                    ->first();

                if (is_null($connector_set))
                    continue;

                DB::table('seat_connector_set_entity')->insert([
                    'set_id'      => $connector_set->id,
                    'entity_type' => 'role',
                    'entity_id'   => $policy->role_id,
                ]);

            }
        }
    }
}
