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

namespace Warlof\Seat\Connector\Teamspeak\Commands;

use Illuminate\Console\Command;
use Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException;
use Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakHelper;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroup;

class TeamspeakGroupsUpdate extends Command
{
    /**
     * @var string
     */
    protected $signature = 'teamspeak:groups:update';

    /**
     * @var string
     */
    protected $description = 'Discovering Teamspeak groups (both server and channel)';

	/**
	 * @throws TeamspeakSettingException
	 * @throws \Seat\Services\Exceptions\SettingException
	 */
    public function handle()
    {
        $ts_username = setting('teamspeak_username', true);
        $ts_password = setting('teamspeak_password', true);
        $ts_hostname = setting('teamspeak_hostname', true);
        $ts_server_query = setting('teamspeak_server_query', true);
        $ts_server_voice = setting('teamspeak_server_port', true);

        if (is_null($ts_username) || is_null($ts_password) || is_null($ts_hostname) || is_null($ts_server_query) ||
            is_null($ts_server_voice)) {
            throw new TeamspeakSettingException("missing teamspeak_username, teamspeak_password, teamspeak_hostname, ".
                "teamspeak_server_query or teamspeak_server_port in settings");
        }

        $ts_server = TeamspeakHelper::connect($ts_username, $ts_password, $ts_hostname, $ts_server_query, $ts_server_voice);

        // type : {0 = template, 1 = normal, 2 = query}
        $server_groups = $ts_server->serverGroupList(['type' => 1]);

        // retrieve the default server group assigned to this instance
        $default_sgid = $ts_server->getInfo()['virtualserver_default_server_group'];

        foreach ($server_groups as $server_group) {

            // skip the default server group as we can do nothing with it
            if ($server_group->sgid == $default_sgid) {
                TeamspeakGroup::destroy($server_group->sgid);
                continue;
            }

            TeamspeakGroup::updateOrCreate(
                [
                    'id' => $server_group->sgid,
                ],
                [
                    'name' => $server_group->name,
                    'is_server_group' => true,
                ]
            );
        }

    }
}
