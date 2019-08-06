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
use Seat\Services\Exceptions\SettingException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;
use Warlof\Seat\Connector\Models\User;

/**
 * Class MigrateTeamspeakUsersToSeatConnector.
 */
class MigrateTeamspeakUsersToSeatConnector extends Migration
{
    public function up()
    {
        if (Schema::hasTable('teamspeak_users')) {

            try {
                $client = TeamspeakClient::getInstance();
            } catch (TeamspeakException | DriverSettingsException | SettingException $e) {
                echo PHP_EOL;
                echo '|------------------------------------------------------------------------------------------------------|' . PHP_EOL;
                echo '| It appears you had an existing SeAT Teamspeak installation.                                          |' . PHP_EOL;
                echo '| Please run "seat-connector:upgrade:teamspeak" command first in order to setup your Teamspeak server. |' . PHP_EOL;
                echo '| Then, run "migrate" again in order to convert your existing installation to the new version.         |' . PHP_EOL;
                echo '|------------------------------------------------------------------------------------------------------|' . PHP_EOL;
                echo PHP_EOL;

                throw $e;
            }

            echo 'Converting old teamspeak users structure to new seat-connector users scheme...' . PHP_EOL;

            $users = DB::table('teamspeak_users')->get();

            foreach ($users as $user) {

                if (! is_null(User::where('connector_type', 'teamspeak')->where('group_id', $user->group_id)->first()))
                    continue;

                try {
                    $teamspeak_user = $client->sendCall('clientGetNameFromUid', [$user->teamspeak_id]);

                    $connector_user = new User();
                    $connector_user->connector_type = 'teamspeak';
                    $connector_user->connector_id = $teamspeak_user['data']['cldbid'];
                    $connector_user->connector_name = $teamspeak_user['data']['name'];
                    $connector_user->unique_id = $user->teamspeak_id;
                    $connector_user->group_id = $user->group_id;
                    $connector_user->created_at = $user->created_at;
                    $connector_user->updated_at = $user->updated_at;
                    $connector_user->save();
                } catch (TeamspeakException $e) {
                    echo sprintf('!! WARNING !! - Impossible to migrate Teamspeak User Account uid:%s, group_id:%s',
                            $user->teamspeak_id, $user->group_id) . PHP_EOL;
                }

            }
        }
    }
}
