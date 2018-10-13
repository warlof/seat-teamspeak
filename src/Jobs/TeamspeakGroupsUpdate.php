<?php
/**
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2018  Warlof Tutsimo <loic.leuilliot@gmail.com>
 *
 * SeAT Teamspeak Connector  is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * SeAT Teamspeak Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Connector\Teamspeak\Jobs;


use Illuminate\Support\Facades\Redis;
use TeamSpeak3_Node_Server;
use Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroup;

class TeamspeakGroupsUpdate extends TeamspeakJobBase
{

    protected $tags = [
        'groups',
    ];

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle()
    {
        // queue a unique orchestrator for the current group
        Redis::funnel('seat-teamspeak-connector:jobs.groups_update')->limit(1)->then(function () {

            $this->updateServerGroups();

            $this->onAfterJob();

        }, function () {
            logger()->warning(sprintf('%s - An other job is already queued', self::class));

            $this->delete();
        });
    }

    private function updateServerGroups()
    {
        // type : {0 = template, 1 = normal, 2 = query}
        $server_groups = collect($this->teamspeak()->serverGroupList(['type' => 1]));

        // retrieve the default server group assigned to this instance
        $default_sgid = $this->teamspeak()->getInfo()['virtualserver_default_server_group'];

        // updating server groups and removing those who are non longer existing or the default one
        TeamspeakGroup::whereNotIn('id', $server_groups->filter(function ($server_group) use ($default_sgid) {

                // skip the default server group as we can do nothing with it
                return $server_group->sgid != $default_sgid;
            })
            ->each(function ($server_group) {

                // either create a new server group entry or update the existing one
                TeamspeakGroup::updateOrCreate(
                    [
                        'id' => $server_group->sgid,
                    ],
                    [
                        'name' => $server_group->name,
                        'is_server_group' => true,
                    ]
                );
            })
            ->pluck('sgid')
            ->flatten()
            ->toArray()
        )
        ->orWhere('id', $default_sgid)
        ->delete();
    }

    /**
     * @return \TeamSpeak3_Node_Server
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     */
    protected function teamspeak(): TeamSpeak3_Node_Server
    {
        if (is_null($this->client))
            $this->client = new TeamspeakSetup();

        return $this->client->getInstance();
    }

    /**
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     */
    private function onAfterJob()
    {
        $this->teamspeak()->logout();
    }

    /**
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     */
    public function failed()
    {
        $this->onAfterJob();
    }
}
