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

namespace Seat\Warlof\Teamspeak\Jobs;

use Seat\Web\Models\User;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;
use Seat\Warlof\Teamspeak\Helpers\TeamspeakHelper;

class TeamspeakAssKicker extends TeamspeakBase
{

    protected $tags = ['teamspeak', 'kick'];

	/**
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
	 */
    public function handle()
    {
        $users = User::all();
        $helper = new TeamspeakHelper;
        $helper->joinTeamspeak();

        foreach ($users as $user) {

            $group_id = $user->group_id;

            $teamspeak_user = TeamspeakUser::where('group_id', $group_id)->first();

			// control that we already know it's Teamspeak ID
            if ($teamspeak_user != null) {
                // search client information using client unique ID
                $user_info = $helper->getTeamspeak()->clientGetNameByUid($teamspeak_user->teamspeak_id);

                $allowed_groups = $helper->allowedGroups($teamspeak_user, true);
                $teamspeak_groups = $helper->getTeamspeak()->clientGetServerGroupsByDbid($user_info['cldbid']);

                $member_of_groups = [];
                foreach ($teamspeak_groups as $teamspeak_group) {
                    if ($teamspeak_group['name'] != "Guest") {
                   		$member_of_groups[] = $teamspeak_group['sgid'];
                    }
                }

                $missing_groups = array_diff($member_of_groups, $allowed_groups);

                if (!empty($missing_groups)) {
                   $helper->processGroupsKick($user_info['cldbid'], $missing_groups);
                   $helper->logEvent($user_info['name'], 'kick', $missing_groups);
                }
            }
        }
        return;
    }
}
