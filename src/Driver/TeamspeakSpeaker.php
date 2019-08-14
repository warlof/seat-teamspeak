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
 * Class TeamspeakSpeaker.
 *
 * @package Warlof\Seat\Connector\Discord\Drivers\Teamspeak
 */
class TeamspeakSpeaker implements IUser
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $unique_id;

    /**
     * @var string
     */
    private $nickname;

    /**
     * @var \Warlof\Seat\Connector\Drivers\ISet[]
     */
    private $server_groups;

    /**
     * TeamspeakSpeaker constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->server_groups = collect();
        $this->hydrate($attributes);
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->unique_id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->nickname;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {

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
    public function getSets(): array
    {
        if ($this->server_groups->isEmpty()) {
            $response = TeamspeakClient::getInstance()->sendCall('serverGroupsByClientID', [
                'cldbid' => $this->id,
            ]);

            foreach ($response['data'] as $group_attributes) {

                $group = TeamspeakClient::getInstance()->getSet($group_attributes['sgid']);

                if (! is_null($group))
                    $this->server_groups->put($group->getId(), $group);
            }
        }

        return $this->server_groups->toArray();
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\ISet $group
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function addSet(ISet $group)
    {
        if (in_array($group, $this->getSets()))
            return;

        TeamspeakClient::getInstance()->sendCall('serverGroupAddClient', [
            'sgid'   => $group->getId(),
            'cldbid' => $this->id,
        ]);

        $this->server_groups->put($group->getId(), $group);
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\ISet $group
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public function removeSet(ISet $group)
    {
        if (! in_array($group, $this->getSets()))
            return;

        TeamspeakClient::getInstance()->sendCall('serverGroupDeleteClient', [
            'sgid'   => $group->getId(),
            'cldbid' => $this->id,
        ]);

        $this->server_groups->pull($group->getId());
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function hydrate(array $attributes)
    {
        $this->id        = $attributes['cldbid'];
        $this->unique_id = $attributes['client_unique_identifier'];
        $this->nickname  = $attributes['client_nickname'];

        return $this;
    }
}
