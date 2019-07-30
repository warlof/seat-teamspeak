<?php
/**
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2019  Warlof Tutsimo <loic.leuilliot@gmail.com>
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

namespace Warlof\Seat\Connector\Drivers\Teamspeak\Driver;

use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\IUser;

/**
 * Class TeamspeakServerGroup.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Driver
 */
class TeamspeakServerGroup implements ISet
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \Warlof\Seat\Connector\Drivers\IUser[]
     */
    private $members;

    /**
     * TeamspeakServerGroup constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->members = collect();
        $this->hydrate($attributes);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function getMembers(): array
    {
        if ($this->members->isEmpty()) {
            $response = TeamspeakClient::getInstance()->sendCall('serverGroupClientList', [
                'sgid'  => $this->id,
                'names' => true,
            ]);

            foreach ($response['data'] as $user_attributes) {
                if (! array_key_exists('cldbid', $user_attributes))
                    continue;

                $member = TeamspeakClient::getInstance()->getUser($user_attributes['cldbid']);

                if (is_null($member)) {
                    $member = new TeamspeakSpeaker($user_attributes);
                    TeamspeakClient::getInstance()->addSpeaker($member);
                }

                $this->members->put($member->getClientId(), $member);
            }
        }

        return $this->members->toArray();
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IUser $user
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function addMember(IUser $user)
    {
        if (in_array($user, $this->getMembers()))
            return;

        TeamspeakClient::getInstance()->sendCall('serverGroupAddClient', [
            'sgid'   => $this->id,
            'cldbid' => $user->getClientId(),
        ]);

        $this->members->put($user->getClientId(), $user);
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IUser $user
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function removeMember(IUser $user)
    {
        if (! in_array($user, $this->getMembers()))
            return;

        TeamspeakClient::getInstance()->sendCall('serverGroupDeleteClient', [
            'sgid'   => $this->id,
            'cldbid' => $user->getClientId(),
        ]);

        $this->members->pull($user->getClientId());
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function hydrate(array $attributes)
    {
        $this->id   = $attributes['sgid'];
        $this->name = $attributes['name'];

        return $this;
    }
}
