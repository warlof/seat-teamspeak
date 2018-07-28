<?php

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
