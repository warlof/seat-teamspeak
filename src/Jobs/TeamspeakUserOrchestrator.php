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
use TeamSpeak3_Adapter_ServerQuery_Exception;
use TeamSpeak3_Node_Server;
use Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakUser;

class TeamspeakUserOrchestrator extends TeamspeakJobBase
{

    /**
     * @var array
     */
    protected $tags = [
        'orchestrator',
    ];

    /**
     * @var TeamspeakUser
     */
    private $user;

    /**
     * @var bool
     */
    private $terminator;

    /**
     * TeamspeakUserOrchestrator constructor.
     * @param TeamspeakUser $user
     * @param bool $terminator
     */
    public function __construct(TeamspeakUser $user, bool $terminator = false)
    {
        logger()->debug(sprintf('%s - Initialising job', self::class), [
            'group_id' => $user->group_id,
            'teamspeak_uid' => $user->teamspeak_id,
        ]);

        $this->user = $user;
        $this->terminator = $terminator;

        array_push($this->tags, sprintf('teamspeak_uid:%s', $this->user->teamspeak_id));
        array_push($this->tags, sprintf('group_id:%d', $this->user->group_id));

        if ($this->terminator) {
            array_push($this->tags, 'terminator');
            logger()->warning(sprintf('%s - Terminator flag has been enabled.', self::class));
        }
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle()
    {
        $key = sprintf('seat-teamspeak-connector:jobs.user_orchestrator_%d', $this->user->group_id);

        // queue a unique orchestrator for the current group
        Redis::funnel($key)->limit(1)->then(function () {

            $this->updateServerGroups();

            $this->onAfterJob();

        }, function () {
            logger()->warning(sprintf('%s - An other job is already queued for %d.', self::class, $this->user->group_id));

            $this->delete();
        });
    }

    /**
     * Update server groups for the current running user
     *
     * @return void
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     */
    private function updateServerGroups()
    {
        try {
            $member_of_groups = [];

            // retrieve server default group
            $default_sgid = $this->teamspeak()->getInfo()['virtualserver_default_server_group'];

            // retrieve teamspeak user information
            $user_info = $this->teamspeak()->clientGetNameByUid($this->user->teamspeak_id);

            // retrieve all groups currently granted to the user
            $teamspeak_groups = $this->teamspeak()->clientGetServerGroupsByDbid($user_info['cldbid']);

            foreach ($teamspeak_groups as $teamspeak_group) {
                if ($teamspeak_group['sgid'] === $default_sgid)
                    continue;

                $member_of_groups[] = $teamspeak_group['sgid'];
            }

            // in case terminator has been turned on or user got revoked token, revoke all groups assigned to the user
            if ($this->terminator || !$this->user->isGranted()) {
                if (!empty($member_of_groups)) {
                    foreach ($member_of_groups as $group)
                        $this->teamspeak()->serverGroupClientDel($group, $user_info['cldbid']);
                }

                return;
            }

            // retrieve all eligible groups for the current user
            $allowed_groups = $this->user->allowedGroups();

            // get the delta
            $missing_groups = array_diff($allowed_groups, $member_of_groups);
            $extra_groups = array_diff($member_of_groups, $allowed_groups);

            logger()->debug(sprintf('%s - Updating user Server Groups', self::class), [
                'group_id' => $this->user->group_id,
                'teamspeak_uid' => $this->user->teamspeak_id,
                'allowed_groups' => $allowed_groups,
                'missing_groups' => $missing_groups,
                'extra_groups' => $extra_groups,
            ]);

            // add all missing groups to the user
            foreach ($missing_groups as $group) {
                logger()->debug(sprintf('%s - Adding user to a new server group.', self::class), [
                    'group_id' => $this->user->group_id,
                    'teamspeak_uid' => $this->user->teamspeak_id,
                    'teamspeak_cldbid' => $user_info['cldbid'],
                    'teamspeak_sgid' => $group,
                ]);

                $this->teamspeak()->serverGroupClientAdd($group, $user_info['cldbid']);
            }

            // remove all extra groups from the user
            foreach ($extra_groups as $group) {
                logger()->debug(sprintf('%s - Removing user from a server group.', self::class), [
                    'group_id' => $this->user->group_id,
                    'teamspeak_uid' => $this->user->teamspeak_id,
                    'teamspeak_cldbid' => $user_info['cldbid'],
                    'teamspeak_sgid' => $group,
                ]);

                $this->teamspeak()->serverGroupClientDel($group, $user_info['cldbid']);
            }
        } catch (TeamSpeak3_Adapter_ServerQuery_Exception $e) {
            // (code: 512) invalid clientID
            if ($e->getReturnCode() === 512)
                $this->user->delete();
        }
    }

    /**
     * @return TeamSpeak3_Node_Server
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
