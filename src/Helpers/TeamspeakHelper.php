<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 11/08/2016
 * Time: 15:59
 */

namespace Seat\Warlof\Teamspeak\Helpers;

use TeamSpeak3;
use Illuminate\Support\Facades\Log;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Corporation\Title;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroup;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupPublic;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupUser;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupRole;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupCorporation;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupAlliance;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupTitle;
use Seat\Warlof\Teamspeak\Models\TeamspeakLog;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\Group;
use Seat\Web\Models\User;

class TeamspeakHelper
{
    private $teamspeak;

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
        $serverQuery = sprintf("serverquery://%s:%s@%s:%s/?server_port=%s&blocking=0", $username, $password,
            $hostname, $server_query_port, $instance_port);

        return TeamSpeak3::factory($serverQuery);

    }

    /**
     * Set the Teamspeak Server object
     *
     * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
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


    public function getTeamspeak()
    {

        return $this->teamspeak;
    }

    

    /**
     * Invite an user to each group
     *
     * @param \TeamSpeak3_Node_Client $teamspeak_client_node
     * @param array $groups
     */
    public function processGroupsInvitation(\TeamSpeak3_Node_Client $teamspeak_client_node, $groups)
    {
        // iterate over each group ID and add the user
        foreach ($groups as $groupId) {
            $this->teamspeak->serverGroupClientAdd($groupId, $teamspeak_client_node->client_database_id);
        }
    }

    /**
     * Kick an user from each group
     *
     * @param \TeamSpeak3_Node_Client $teamspeak_client_node
     * @param $groups
     */
    public function processGroupsKick(\TeamSpeak3_Node_Client $teamspeak_client_node, $groups)
    {
        foreach ($groups as $groupId) {
            $this->teamspeak->serverGroupClientDel($groupId, $teamspeak_client_node->client_database_id);
        }
    }

    public function logEvent($user, $event_type, $groups)
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
	

public function allowedGroups($teamspeak_user, $private)
    {
        $groups = [];
		
		Log::info('Starting process with teamspeak user: '.$teamspeak_user);

        $user = User::where('group_id', $teamspeak_user->group_id)->first();
		
		Log::info('Getting Group ID for Allow: '.$user);
		
        $group_id = $teamspeak_user->group_id;
		
		Log::info('Launching Allow with ID: '.$group_id);

        $rows = TeamspeakGroupUser::join('groups', 'teamspeak_group_users.group_id', '=', 'groups.id')
            ->join('teamspeak_groups', 'teamspeak_group_users.tsgrp_id' , '=', 'teamspeak_groups.id')
            ->where('groups.id', $group_id)
            ->where('teamspeak_groups.is_server_group', (int) $private)
            ->select('tsgrp_id')
            ->union(
                // fix model declaration calling the table directly
                TeamspeakGroupRole::join('group_role', 'teamspeak_group_roles.role_id', '=', 'group_role.role_id')
            ->join('teamspeak_groups', 'teamspeak_group_roles.tsgrp_id' , '=', 'teamspeak_groups.id')
                    ->where('group_role.group_id', $group_id)
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('tsgrp_id')
            )->union(
                TeamspeakGroupCorporation::join('character_infos', 'teamspeak_group_corporations.corporation_id', '=', 'character_infos.corporation_id')
            ->join('teamspeak_groups', 'teamspeak_group_corporations.tsgrp_id' , '=', 'teamspeak_groups.id')
                    ->where('character_infos.character_id', $user->id)
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('tsgrp_id')
            )->union(
                TeamspeakGroupAlliance::join('character_infos', 'teamspeak_group_alliances.alliance_id', '=', 'character_infos.alliance_id')
            ->join('teamspeak_groups', 'teamspeak_group_alliances.tsgrp_id' , '=', 'teamspeak_groups.id')
                    ->where('character_infos.character_id', $user->id)
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('tsgrp_id')
            )->union(
                TeamspeakGroupPublic::join('teamspeak_groups', 'teamspeak_group_public.tsgrp_id', '=', 'teamspeak_groups.id')
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('tsgrp_id')
            )->get();

        
		$groups = $rows->unique('tsgrp_id')->pluck('tsgrp_id')->toArray();
		
        return $groups;
    }

}
