<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 19:01
 */

namespace Seat\Warlof\Teamspeak\Jobs\Workers;

use Seat\Eveapi\Models\Eve\ApiKey;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;

class TeamspeakReceptionist extends AbstractTeamspeak
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

        // invite user only if both account are subscribed and keys active
        if ($this->isEnabledKey($keys) && $this->isActive($keys)) {
            // get the attached teamspeak user
            $teamspeakUser = TeamspeakUser::where('user_id', $this->user->id)->first();

            // control that we already know it's Teamspeak ID
            if ($teamspeakUser != null) {
                // search client information using client unique ID
                $userDbid = $this->getTeamspeak()->clientFindDb($teamspeakUser->teamspeak_id, true);
                $userInfo = $this->getTeamspeak()->clientInfoDb($userDbid);

                $allowedGroups = $this->allowedGroups($teamspeakUser, true);
                $teamspeakGroups = $this->getTeamspeak()->clientGetServerGroupsByDbid($userInfo['client_database_id']);

                $memberOfGroups = [];
                foreach ($teamspeakGroups as $g) {
                    $memberOfGroups[] = $g['sgid'];
                }

                $missingGroups = array_diff($allowedGroups, $memberOfGroups);

                if (!empty($missingGroups)) {
                    $this->processGroupsInvitation($userInfo, $missingGroups);
                    $this->logEvent('invite', $missingGroups);
                }
            }
        }
        sleep(5);
        return;
    }

    /**
     * Invite an user to each group
     * 
     * @param \TeamSpeak3_Node_Client $teamspeak_client_node
     * @param array $groups
     */
    private function processGroupsInvitation($teamspeak_client_node, $groups)
    {
        // iterate over each group ID and add the user
        foreach ($groups as $groupId) {
            $this->getTeamspeak()->serverGroupClientAdd($groupId, $teamspeak_client_node['client_database_id']);
        }
    }
}
