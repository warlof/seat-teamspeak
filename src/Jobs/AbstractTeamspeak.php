<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 18/06/2016
 * Time: 21:01
 */

namespace Seat\Warlof\Teamspeak\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Seat\Eveapi\Models\Account\AccountStatus;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException;
use Seat\Warlof\Teamspeak\Helpers\TeamspeakHelper;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupPublic;
use Seat\Warlof\Teamspeak\Models\TeamspeakLog;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;
use Seat\Web\Models\User;

abstract class AbstractTeamspeak
{
    /**
     * @var User the user we're checking access
     */
    protected $user;

    /**
     * @var \TeamSpeak3_Adapter_Abstract The Teamspeak Server object
     */
    private $teamspeak;

    /**
     * Set the Teamspeak Server object
     *
     * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function call()
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

        $this->teamspeak = TeamspeakHelper::connect($tsUsername, $tsPassword, $tsHostname, $tsServerQuery, $tsServerPort);
    }

    /**
     * Enable to affect an User object to the current Job
     *
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        
        return $this;
    }

    /**
     * @return \TeamSpeak3_Adapter_Abstract
     */
    protected function getTeamspeak()
    {
        return $this->teamspeak;
    }

    /**
     * Return true if all API Key are still enable
     *
     * @param Collection $keys
     * @return bool
     */
    protected function isEnabledKey(Collection $keys)
    {
        // count keys with enable value and compare it to total keys number
        $enabledKeys = $keys->filter(function($item){
            return $item->enabled == 1;
        })->count();
        
        if ($enabledKeys == $keys->count() && $keys->count() != 0) {
            return true;
        }

        return false;
    }

    /**
     * Return true if at least one account is still paid until now
     *
     * @param Collection $keys
     * @return bool
     */
    protected function isActive(Collection $keys)
    {
        // iterate over keys and compare the paidUntil field value to current date
        foreach ($keys as $key) {
            if (AccountStatus::where('keyID', $key->key_id)
                ->whereDate('paidUntil', '>=', date('Y-m-d'))
                ->count() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine all groups in which an user is allowed to be
     *
     * @param TeamspeakUser $teamspeak_user
     * @param boolean $private Determine if groups should be server group or channel group
     *
     * @return array
     */
    protected function allowedGroups(TeamspeakUser $teamspeak_user, $private)
    {
        $groups = [];

        $rows = User::join('teamspeak_group_users', 'teamspeak_group_users.user_id', '=', 'users.id')
            ->join('teamspeak_groups', 'teamspeak_group_users.group_id', '=', 'teamspeak_groups.id')
            ->select('group_id')
            ->where('users.id', $teamspeak_user->user_id)
            ->where('teamspeak_groups.is_server_group', (int) $private)
            ->union(
                // fix model declaration calling the table directly
                DB::table('role_user')->join('teamspeak_group_roles', 'teamspeak_group_roles.role_id', '=',
                    'role_user.role_id')
                    ->join('teamspeak_groups', 'teamspeak_group_roles.group_id', '=', 'teamspeak_groups.id')
                    ->where('role_user.user_id', $teamspeak_user->user_id)
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('group_id')
            )->union(
                ApiKey::join('account_api_key_info_characters', 'account_api_key_info_characters.keyID', '=',
                    'eve_api_keys.key_id')
                    ->join('teamspeak_group_corporations', 'teamspeak_group_corporations.corporation_id', '=',
                        'account_api_key_info_characters.corporationID')
                    ->join('teamspeak_groups', 'teamspeak_group_corporations.group_id', '=', 'teamspeak_groups.id')
                    ->where('eve_api_keys.user_id', $teamspeak_user->user_id)
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('group_id')
            )->union(
                CharacterSheet::join('teamspeak_group_alliances', 'teamspeak_group_alliances.alliance_id', '=',
                    'character_character_sheets.allianceID')
                    ->join('teamspeak_groups', 'teamspeak_group_alliances.group_id', '=', 'teamspeak_groups.id')
                    ->join('account_api_key_info_characters', 'account_api_key_info_characters.characterID', '=',
                        'character_character_sheets.characterID')
                    ->join('eve_api_keys', 'eve_api_keys.key_id', '=', 'account_api_key_info_characters.keyID')
                    ->where('eve_api_keys.user_id', $teamspeak_user->user_id)
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('group_id')
            )->union(
                TeamspeakGroupPublic::join('teamspeak_groups', 'teamspeak_group_public.group_id', '=', 'teamspeak_groups.id')
                    ->where('teamspeak_groups.is_server_group', (int) $private)
                    ->select('group_id')
            )->get();

        foreach ($rows as $row) {
            $groups[] = $row->group_id;
        }

        return $groups;
    }

    protected function logEvent($event_type, $groups)
    {
        $message = '';

        switch ($event_type)
        {
            case 'invite':
                $message = 'The user ' . $this->user->name . ' has been invited to following groups : ' .
                    implode(',', $groups);
                break;
            case 'kick':
                $message = 'The user ' . $this->user->name . ' has been kicked from following groups : ' .
                    implode(',', $groups);
                break;
        }

        TeamspeakLog::create([
            'event' => $event_type,
            'message' => $message
        ]);
    }
}
