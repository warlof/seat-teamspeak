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

class TeamspeakInviter extends TeamspeakJobBase
{

    protected $tags = ['teamspeak', 'invite'];

	/**
	 * @throws \Seat\Services\Exceptions\SettingException
	 * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
	 */
    public function handle()
    {
		Log::info('Running Inviter.');
		
        $users = User::all();
        $thelper = new TeamspeakHelper;
        $thelper->joinTeamspeak();

        foreach ($users as $user) {
			
			Log::info('User: '.$user->id);

            $group_id = $user->group_id;
			Log::info('Group: '.$group_id);

            $teamspeakUser = TeamspeakUser::where('group_id', $group_id)->first();
			
			Log::info('TS User: '.$teamspeakUser);

            // control that we already know it's Teamspeak ID
            if ($teamspeakUser != null) {
                // search client information using client unique ID
                $userInfo = $thelper->getTeamspeak()->clientGetByUid($teamspeakUser->teamspeak_id, true);
				
				Log::info('TS User Info: '.$userInfo);

                $allowedGroups = $thelper->allowedGroups($teamspeakUser, true);
				
				Log::info('TS Allowed Groups: '.implode(" ",$allowedGroups));
				
                $teamspeakGroups = $thelper->getTeamspeak()->clientGetServerGroupsByDbid($userInfo->client_database_id);
				
				Log::info('Teamspeak Groups: '.var_dump(" ",$teamspeakGroups));

                $memberOfGroups = [];
                foreach ($teamspeakGroups as $g) {
                   $memberOfGroups[] = $g['sgid'];
                }

                $missingGroups = array_diff($allowedGroups, $memberOfGroups);
				
				Log::info('Missing Groups: '.implode(" ",$missingGroups));
				
                if (!empty($missingGroups)) {
                   $thelper->processGroupsInvitation($userInfo, $missingGroups);
                   $thelper->logEvent($userInfo, 'invite', $missingGroups);
                }
            }
        }
        return;
    }
}
