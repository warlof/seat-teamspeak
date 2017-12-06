<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Warlof\Teamspeak\Commands;


use Illuminate\Console\Command;
use Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException;
use Seat\Warlof\Teamspeak\Helpers\TeamspeakHelper;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroup;

class TeamspeakGroupsUpdate extends Command
{
    protected $signature = 'teamspeak:groups:update';

    protected $description = 'Discovering Teamspeak groups (both server and channel)';

    public function __construct()
    {
        parent::__construct();
    }

	/**
	 * @throws TeamspeakSettingException
	 * @throws \Seat\Services\Exceptions\SettingException
	 */
    public function handle()
    {
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

        $tsServer = TeamspeakHelper::connect($tsUsername, $tsPassword, $tsHostname, $tsServerQuery, $tsServerPort);

        // type : {0 = template, 1 = normal, 2 = query}
        $groups = $tsServer->serverGroupList(['type' => 1]);

        foreach ($groups as $group) {
            $teamspeakGroup = TeamspeakGroup::find($group->sgid);

            if ($teamspeakGroup == null) {
                TeamspeakGroup::create([
                    'id' => $group->sgid,
                    'name' => $group->name,
                    'is_server_group' => true,
                ]);

                continue;
            }

            $teamspeakGroup->update([
                'name' => $group->name
            ]);
        }

    }
}
