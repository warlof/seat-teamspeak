<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 11/08/2016
 * Time: 15:59
 */

namespace Seat\Warlof\Teamspeak\Helpers;

use TeamSpeak3;

class TeamspeakHelper
{
    /**
     * @param $tsUsername
     * @param $tsPassword
     * @param $tsHostname
     * @param $tsServerQuery
     * @param $tsServerPort
     * @return \TeamSpeak3_Adapter_Abstract
     */
    public static function connect($tsUsername, $tsPassword, $tsHostname, $tsServerQuery, $tsServerPort)
    {
        $serverQuery = sprintf("serverquery://%s:%s@%s:%s/?server_port=%s&blocking=0", $tsUsername, $tsPassword, 
            $tsHostname, $tsServerQuery, $tsServerPort);

        return \TeamSpeak3::factory($serverQuery);
    }

    /**
     * Determine all channels into which an user is allowed to be
     *
     * @param SlackUser $teamspeakUser
     * @param bool $private
     * @return array
     */
    public static function allowedChannels($user_id) : array
    {
        $channels = [];

        $rows = User::join('teamspeak_group_users', 'teamspeak_group_users.user_id', '=', 'users.id')
            ->join('teamspeak_groups', 'teamspeak_group_users.group_id', '=', 'teamspeak_groups.id')
            ->select('group_id')
            ->where('users.id', $user_id)
            ->where('teamspeak_groups.is_group', (int) $private)
            ->where('teamspeak_groups.is_general', (int) false)
            ->union(
            // fix model declaration calling the table directly
                DB::table('role_user')->join('teamspeak_group_roles', 'teamspeak_group_roles.role_id', '=',
                    'role_user.role_id')
                    ->join('teamspeak_groups', 'teamspeak_group_roles.group_id', '=', 'teamspeak_groups.id')
                    ->where('role_user.user_id', $user_id)
                    ->where('teamspeak_groups.enabled', (int) false)
                    ->select('group_id')
            )->union(
                ApiKey::join('account_api_key_info_characters', 'account_api_key_info_characters.keyID', '=',
                    'eve_api_keys.key_id')
                    ->join('teamspeak_group_corporations', 'teamspeak_group_corporations.corporation_id', '=',
                        'account_api_key_info_characters.corporationID')
                    ->join('teamspeak_groups', 'teamspeak_group_corporations.group_id', '=', 'teamspeak_groups.id')
                    ->where('eve_api_keys.user_id', $user_id)
                    ->where('teamspeak_groups.enabled', (int) true)
                    ->select('group_id')
            )->union(
                ApiKey::join('account_api_key_info_characters', 'account_api_key_info_characters.keyID', '=',
                    'eve_api_keys.key_id')
                    ->join('character_character_sheet_corporation_titles',
                        'character_character_sheet_corporation_titles.characterID', '=',
                        'account_api_key_info_characters.characterID')
                    ->join('teamspeak_group_titles', function($join){
                        $join->on('teamspeak_group_titles.corporation_id', '=',
                            'account_api_key_info_characters.corporationID');
                        $join->on('teamspeak_group_titles.title_id', '=',
                            'character_character_sheet_corporation_titles.titleID');
                    })
                    ->join('teamspeak_groups', 'teamspeak_group_titles.group_id', '=', 'teamspeak_groups.id')
                    ->where('eve_api_keys.user_id', $user_id)
                    ->where('teamspeak_groups.is_group', (int) $private)
                    ->where('teamspeak_groups.is_general', (int) false)
                    ->select('group_id')
            )->union(
                CharacterSheet::join('teamspeak_group_alliances', 'teamspeak_group_alliances.alliance_id', '=',
                    'character_character_sheets.allianceID')
                    ->join('teamspeak_groups', 'teamspeak_group_alliances.group_id', '=', 'teamspeak_groups.id')
                    ->join('account_api_key_info_characters', 'account_api_key_info_characters.characterID', '=',
                        'character_character_sheets.characterID')
                    ->join('eve_api_keys', 'eve_api_keys.key_id', '=', 'account_api_key_info_characters.keyID')
                    ->where('eve_api_keys.user_id', $user_id)
                    ->where('teamspeak_groups.enabled', (int) true)
                    ->select('group_id')
            )->union(
                TeamspeakGroupPublic::join('teamspeak_groups', 'teamspeak_group_public.group_id', '=', 'teamspeak_groups.id')
                    ->where('teamspeak_groups.enabled', (int) true)
                    ->select('group_id')
            )->get();

        foreach ($rows as $row) {
            $channels[] = $row->group_id;
        }

        return $channels;
    }
}

