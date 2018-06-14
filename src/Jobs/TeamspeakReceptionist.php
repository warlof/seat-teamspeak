<?php

namespace Seat\Warlof\Teamspeak\Jobs;

use Illuminate\Support\Facades\Log;
use Teamspeak3;
use Seat\Web\Models\User;
use Seat\Eveapi\Jobs\EsiBase;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;
use Seat\Eseye\Configuration;
use Seat\Eseye\Eseye;
use Seat\Eseye\Exceptions\EsiScopeAccessDeniedException;
use Seat\Eseye\Exceptions\RequestFailedException;
use Seat\Warlof\Teamspeak\Helpers\TeamspeakHelper;

class TeamspeakReceptionist extends TeamspeakBase
{

    protected $tags = ['teamspeak', 'invite'];

	/**
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
	 */
    public function handle()
    {
		$users = User::all();
        $thelper = new TeamspeakHelper;
        $thelper->joinTeamspeak();

        foreach ($users as $user) {
			
			$group_id = $user->group_id;
			
            $teamspeak_user = TeamspeakUser::where('group_id', $group_id)->first();
			
			// control that we already know it's Teamspeak ID
            if ($teamspeak_user != null) {
                // search client information using client unique ID
                $user_info = $thelper->getTeamspeak()->clientGetNameByUid($teamspeak_user->teamspeak_id, true);
				
				$allowed_groups = $thelper->allowedGroups($teamspeak_user, true);
				
				$teamspeak_groups = $thelper->getTeamspeak()->clientGetServerGroupsByDbid($user_info['cldbid']);
				
				$member_of_groups = [];
                foreach ($teamspeak_groups as $g) {
                   $member_of_groups[] = $g['sgid'];
                }

                $missing_groups = array_diff($allowed_groups, $member_of_groups);
				
				if (!empty($missing_groups)) {
                   $thelper->processGroupsInvitation($user_info['cldbid'], $missing_groups);
                   $thelper->logEvent($user_info['name'], 'invite', $missing_groups);
                }
            }
        }
        return;
    }
}
