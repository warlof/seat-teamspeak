<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Seat\Warlof\Teamspeak\Jobs;

use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Warlof\Teamspeak\Exceptions\TeamspeakServerGroupException;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;

class TeamspeakReceptionist extends AbstractTeamspeak
{

    public function call()
    {
        // call the parent call method in order to load the Teamspeak Server object
        parent::call();

        // get all Api Key owned by the user
        $keys = ApiKey::where('user_id', $this->user->id)->get();

        // invite user only if both account are subscribed and keys active
        if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
            // get the attached teamspeak user
            $teamspeakUser = TeamspeakUser::where('user_id', $this->user->id)->first();
            // control that we already know it's Teamspeak ID
            if ($teamspeakUser != null) {
                // search client information using client unique ID
                $userInfo = $this->getTeamspeak()->clientGetByUid($teamspeakUser->teamspeak_id, true);

                $allowedGroups = $this->allowedGroups($teamspeakUser, true);
                $memberOfGroups = $this->getTeamspeak()->clientGetServerGroupsByDbid($userInfo->client_database_id);
                $missingGroups = array_diff($allowedGroups, $memberOfGroups);

                if (!empty($missingGroups)) {
                    $this->processGroupsInvitation($userInfo, $missingGroups);
                    $this->logEvent('invite', $missingGroups);
                }
            }
        }

        return;
    }

    /**
     * Invite an user to each group
     * 
     * @param \TeamSpeak3_Node_Client $teamspeakClientNode
     * @param array $groups
     * @throws TeamspeakServerGroupException
     */
    private function processGroupsInvitation(\TeamSpeak3_Node_Client $teamspeakClientNode, $groups)
    {
        // iterate over each group ID and add the user
        foreach ($groups as $groupId) {
            $this->getTeamspeak()->serverGroupClientAdd($groupId, $teamspeakClientNode->client_database_id);
        }
    }
}
