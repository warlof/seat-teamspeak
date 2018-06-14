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
        $ts_username = setting('teamspeak_username', true);
        $ts_password = setting('teamspeak_password', true);
        $ts_hostname = setting('teamspeak_hostname', true);
        $ts_server_query = setting('teamspeak_server_query', true);
        $ts_server_voice = setting('teamspeak_server_port', true);

        if ($ts_username == null || $ts_password == null || $ts_hostname == null || $ts_server_query == null ||
            $ts_server_voice == null) {
            throw new TeamspeakSettingException("missing teamspeak_username, teamspeak_password, teamspeak_hostname, ".
                "teamspeak_server_query or teamspeak_server_port in settings");
        }

        $ts_server = TeamspeakHelper::connect($ts_username, $ts_password, $ts_hostname, $ts_server_query, $ts_server_voice);

        // type : {0 = template, 1 = normal, 2 = query}
        $serer_groups = $ts_server->serverGroupList(['type' => 1]);

        foreach ($serer_groups as $server_group) {
            $teamspeak_group = TeamspeakGroup::find($server_group->sgid);

            if ($teamspeak_group == null) {
                TeamspeakGroup::create([
                    'id' => $server_group->sgid,
                    'name' => $server_group->name,
                    'is_server_group' => true,
                ]);

                continue;
            }

            $teamspeak_group->update([
                'name' => $server_group->name
            ]);
        }

    }
}
