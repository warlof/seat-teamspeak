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

namespace Warlof\Seat\Connector\Teamspeak\Helpers;

use TeamSpeak3;
use Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakUser;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupPublic;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupUser;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupRole;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupCorporation;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupAlliance;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakLog;

class TeamspeakHelper
{
    /**
     * @var \TeamSpeak3_Node_Server
     */
    private $teamspeak;

    /**
     * @return \TeamSpeak3_Node_Server
     */
    public function getTeamspeak()
    {
        return $this->teamspeak;
    }

    /**
     * @param $username
     * @param $password
     * @param $hostname
     * @param $server_query_port
     * @param $instance_port
     *
     * @return \TeamSpeak3_Adapter_Abstract
     */
    public static function connect( $username, $password, $hostname, $server_query_port, $instance_port)
    {
        $server_query = sprintf("serverquery://%s:%s@%s:%s/?server_port=%s&blocking=0&nickname=SeAT", $username, $password,
            $hostname, $server_query_port, $instance_port);

        return TeamSpeak3::factory($server_query);
    }

    /**
     * Set the Teamspeak Server object
     *
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function joinTeamspeak()
    {
        // load token and team uri from settings
        $tsUsername = setting('teamspeak_username', true);
        $tsPassword = setting('teamspeak_password', true);
        $tsHostname = setting('teamspeak_hostname', true);
        $tsServerQuery = setting('teamspeak_server_query', true);
        $tsServerPort = setting('teamspeak_server_port', true);

        if ($tsUsername == null || $tsPassword == null || $tsHostname == null || $tsServerQuery == null ||
            $tsServerPort == null) {
            throw new TeamspeakSettingException("missing teamspeak_username, teamspeak_password, teamspeak_hostname, ".
                "teamspeak_server_query or teamspeak_server_port in settings");
        }

        $this->teamspeak = $this->connect($tsUsername, $tsPassword, $tsHostname, $tsServerQuery, $tsServerPort);
    }


    /**
     * Invite an user to each group
     *
     * @param $client_dbid
     * @param array $groups
     */
    public function processGroupsInvitation($client_dbid, $groups)
    {
        // iterate over each group ID and add the user
        foreach ($groups as $group_id) {
            $this->teamspeak->serverGroupClientAdd($group_id, $client_dbid);
        }
    }

    /**
     * Kick an user from each group
     *
     * @param $client_dbid
     * @param $groups
     */
    public function processGroupsKick($client_dbid, $groups)
    {
        foreach ($groups as $group_id) {
            $this->teamspeak->serverGroupClientDel($group_id, $client_dbid);
        }
    }

    /**
     * @param $user
     * @param $event_type
     * @param $groups
     */
    public static function logEvent($user, $event_type, $groups)
    {
        $message = '';

        switch ($event_type)
        {
            case 'invite':
                $message = 'The user ' . $user . ' has been invited to following groups : ' .
                    implode(',', $groups);
                break;
            case 'kick':
                $message = 'The user ' . $user . ' has been kicked from following groups : ' .
                    implode(',', $groups);
                break;
        }

        TeamspeakLog::create([
            'event' => $event_type,
            'message' => $message
        ]);
    }
	

    public static function allowedGroups(TeamspeakUser $teamspeak_user, bool $private)
    {
		$rows = TeamspeakGroupUser::join('groups', 'teamspeak_group_users.group_id', '=', 'groups.id')
            ->join('teamspeak_groups', 'teamspeak_group_users.teamspeak_sgid' , '=', 'teamspeak_groups.id')
            ->where('groups.id', $teamspeak_user->group->id)
            ->where('teamspeak_groups.is_server_group', (int) $private)
            ->select('teamspeak_sgid')
            ->union(
                // fix model declaration calling the table directly
                TeamspeakGroupRole::join('group_role', 'teamspeak_group_roles.role_id', '=', 'group_role.role_id')
            ->join('teamspeak_groups', 'teamspeak_group_roles.teamspeak_sgid' , '=', 'teamspeak_groups.id')
                    ->where('group_role.group_id', $teamspeak_user->group_id)
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('teamspeak_sgid')
            )->union(
                TeamspeakGroupCorporation::join('character_infos', 'teamspeak_group_corporations.corporation_id', '=', 'character_infos.corporation_id')
            ->join('teamspeak_groups', 'teamspeak_group_corporations.teamspeak_sgid' , '=', 'teamspeak_groups.id')
                    ->whereIn('character_infos.character_id', $teamspeak_user->group->users->pluck('id')->toArray())
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('teamspeak_sgid')
            )->union(
                TeamspeakGroupAlliance::join('character_infos', 'teamspeak_group_alliances.alliance_id', '=', 'character_infos.alliance_id')
            ->join('teamspeak_groups', 'teamspeak_group_alliances.teamspeak_sgid' , '=', 'teamspeak_groups.id')
                    ->whereIn('character_infos.character_id', $teamspeak_user->group->users->pluck('id')->toArray())
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('teamspeak_sgid')
            )->union(
                TeamspeakGroupPublic::join('teamspeak_groups', 'teamspeak_group_public.teamspeak_sgid', '=', 'teamspeak_groups.id')
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('teamspeak_sgid')
            )->get();
        
		return $rows->unique('teamspeak_sgid')->pluck('teamspeak_sgid')->toArray();
    }
}
