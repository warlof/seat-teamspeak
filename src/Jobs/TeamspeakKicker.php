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

class TeamspeakKicker extends TeamspeakJobBase
{

    protected $tags = ['teamspeak', 'kick'];

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

            $teamspeakUser = TeamspeakUser::where('group_id', $group_id)->first();

Log::info("Teamspeak User: " . $teamspeakUser . " And User ID " . $user->id);
Log::info("Teamspeak Handler: " . $thelper->getTeamspeak());

            // control that we already know it's Teamspeak ID
            if ($teamspeakUser != null) {
                // search client information using client unique ID
                $userInfo = $thelper->getTeamspeak()->clientGetByUid($teamspeakUser->teamspeak_id, true);

                $allowedGroups = $thelper->allowedGroups($teamspeakUser, true);
                $teamspeakGroups = $thelper->getTeamspeak()->clientGetServerGroupsByDbid($userInfo->client_database_id);

                $memberOfGroups = [];
                foreach ($teamspeakGroups as $g) {
                   $memberOfGroups[] = $g['sgid'];
                }

                $missingGroups = array_diff($memberOfGroups, $allowedGroups);

                if (!empty($missingGroups)) {
                   $thelper->processGroupsKick($userInfo, $missingGroups);
                   $thelper->logEvent($userInfo, 'kick', $missingGroups);
                }
            }
        }
        return;
    }
}
