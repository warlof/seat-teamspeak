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

namespace Warlof\Seat\Connector\Teamspeak\Http\Controllers;

use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Web\Http\Controllers\Controller;
use TeamSpeak3_Node_Client;
use Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException;
use Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakHelper;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakUser;

class TeamspeakController extends Controller
{
    /**
     * @var \TeamSpeak3_Node_Server
     */
    private $teamspeak;

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function getRegisterUser() {
        $main_character = auth()->user()->group->main_character;

        if (! $main_character) {
            redirect()->back()->with('error', 'Could not find your Main Character.  Check your Profile for the correct Main.');
        }

        $corp = CorporationInfo::find($main_character->corporation_id);

        if (! $corp) {
            redirect()->back()->with('error', 'Could not find your Corporation.  Please have your CEO upload a Corp API key to this website.');
        }

        $teamspeak_username = $this->getTeamspeakFormattedNickname();

        return view('teamspeak::register', compact('teamspeak_username'));
    }

    /**
     * @return string
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     * @throws \TeamSpeak3_Adapter_ServerQuery_Exception
     */
    public function postGetUserUid() {

        $this->getTeamspeak();

        $user_list = $this->teamspeak->clientList();

        foreach ($user_list as $user) {
            $nickname = preg_replace('/’/', '\'', $user->client_nickname->toString());

            if ($nickname === $this->getTeamspeakFormattedNickname()) {
                $uid = $user->client_unique_identifier->toString();
                $found_user = [];
                $found_user['id'] = $uid;
                $found_user['nick'] = $nickname;
                $teamspeak_user = $this->postRegisterUser($uid);

                // search client information using client unique ID
                $user_info = $this->teamspeak->clientGetByUid($teamspeak_user->teamspeak_id);

                $allowed_groups = TeamspeakHelper::allowedGroups($teamspeak_user, true);
                $teamspeak_groups = $this->teamspeak->clientGetServerGroupsByDbid($user->client_database_id);
                $member_of_groups = [];

                foreach ($teamspeak_groups as $g) {
                    $member_of_groups[] = $g['sgid'];
                }

                $missing_groups = array_diff($allowed_groups, $member_of_groups);

                if (! empty($missing_groups)) {
                    $this->processGroupsInvitation($user_info, $missing_groups);
                    TeamspeakHelper::logEvent($nickname, 'invite', $missing_groups);
                }

                return response()->json($found_user);
            }
        }

        return response()->json([
            'error' => 'Unable to retrieve you on Teamspeak. Ensure you have the proper nickname.',
        ], 404);
    }

    /**
     * @return bool|string
     * @throws \Seat\Services\Exceptions\SettingException
     */
    private function getTeamspeakFormattedNickname()
    {
        $main_character = auth()->user()->group->main_character;

        $teamspeak_name = $main_character->name;

        if (setting('teamspeak_tags', true) === true) {
            $corp = CorporationInfo::find($main_character->corporation_id);
            $teamspeak_name = sprintf('%s | %s', $corp->ticker, $main_character->name);
        }

        // Teamspeak has a 30 char limit on names. Trim it.
        return substr($teamspeak_name, 0, 30);
    }

    /**
     * Invite an user to each group
     *
     * @param \TeamSpeak3_Node_Client $teamspeak_client_node
     * @param array $teamspeak_groups
     */
    private function processGroupsInvitation(TeamSpeak3_Node_Client $teamspeak_client_node, $teamspeak_groups)
    {
        // iterate over each group ID and add the user
        foreach ($teamspeak_groups as $teamspeak_sgid) {
            $this->teamspeak->serverGroupClientAdd($teamspeak_sgid, $teamspeak_client_node->client_database_id);
        }
    }

    /**
     * @param $uid
     */
    private function postRegisterUser($uid)
    {
        $group_id = auth()->user()->group->id;
        
        $ts_user = TeamspeakUser::find($group_id);
        if (is_null($ts_user)) {
            TeamspeakUser::create([
                'group_id' => $group_id,
                'teamspeak_id' => $uid
            ]);
        } else {
            $ts_user->teamspeak_id = $uid;
            $ts_user->save();
        }

        return $ts_user;
    }

    /**
     * Set the Teamspeak Server object
     *
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     */
    private function getTeamspeak()
    {
        // load token and team uri from settings
        $ts_username = setting('teamspeak_username', true);
        $ts_password = setting('teamspeak_password', true);
        $ts_hostname = setting('teamspeak_hostname', true);
        $ts_server_query = setting('teamspeak_server_query', true);
        $ts_server_voice = setting('teamspeak_server_port', true);

        if ($ts_username == null || $ts_password == null || $ts_hostname == null || $ts_server_query == null ||
            $ts_server_voice == null) {
            throw new TeamspeakSettingException("missing teamspeak_username, teamspeak_password, teamspeak_hostname, ".
                "teamspeak_server_query or teamspeak_server_port in settings");
        }

        $this->teamspeak = TeamspeakHelper::connect($ts_username, $ts_password, $ts_hostname, $ts_server_query, $ts_server_voice);
    }
}
