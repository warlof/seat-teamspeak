<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:04
 */

namespace Seat\Warlof\Teamspeak\Jobs;

use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;

class TeamspeakAssKicker extends AbstractTeamspeak
{

	/**
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
	 */
    public function call()
    {
        // call the parent call method in order to load the Teamspeak Server object
        parent::call();

        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();
        // get the Teamspeak User
        $teamspeakUser = TeamspeakUser::where('user_id', $this->user->id)
            ->whereNotNull('teamspeak_id')
            ->first();

        if ($teamspeakUser != null) {
            // get channels into which current user is already member
            $userInfo = $this->getTeamspeak()->clientGetByUid($teamspeakUser->teamspeak_id, true);
            $teamspeakGroups = $this->getTeamspeak()->clientGetServerGroupsByDbid($userInfo->client_database_id);

            $memberOfGroups = [];
            foreach ($teamspeakGroups as $g) {
                $memberOfGroups[] = $g['sgid'];
            }

            // if key are not valid OR account no longer paid
            // kick the user from all channels to which he's member
            if ($this->isEnabledKey($keys) == false || $this->isActive($keys) == false) {

                if (!empty($groups)) {
                    $this->processGroupsKick($userInfo, $groups);
                    $this->logEvent('kick', $groups);
                }

                return;
            }

            // in other way, compute the gap and kick only the user
            // to channel from which he's no longer granted to be in
            $allowedGroups = $this->allowedGroups($teamspeakUser, true);
            $extraGroups = array_diff($memberOfGroups, $allowedGroups);

            // remove granted channels from channels in which user is already in and kick him
            if (!empty($extraGroups)) {
                $this->logEvent('kick', $extraGroups);
                $this->processGroupsKick($userInfo, $extraGroups);
                $this->logEvent('kick', $extraGroups);
            }
        }

        return;
    }

    /**
     * Kick an user from each group
     *
     * @param \TeamSpeak3_Node_Client $teamspeak_client_node
     * @param $groups
     */
    private function processGroupsKick(\TeamSpeak3_Node_Client $teamspeak_client_node, $groups)
    {
        foreach ($groups as $groupId) {
            $this->getTeamspeak()->serverGroupClientDel($groupId, $teamspeak_client_node->client_database_id);
        }
    }

}
