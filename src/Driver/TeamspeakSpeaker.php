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
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Exceptions\DriverException;

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
     * @return bool
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function setName(string $name): bool
    {
        return false;
    }

    /**
     * @return array
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function getSets(): array
    {
        if ($this->server_groups->isEmpty()) {
            try {
                $this->server_groups = collect(TeamspeakClient::getInstance()->getSpeakerServerGroups($this));
            } catch (TeamspeakException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->server_groups->toArray();
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\ISet $group
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function addSet(ISet $group)
    {
        if (in_array($group, $this->getSets()))
            return;

        try {
            TeamspeakClient::getInstance()->addSpeakerToServerGroup($this, $group);
        } catch (TeamspeakException $e) {
            logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
            throw new DriverException($e->getMessage(), $e->getCode(), $e);
        }

        $this->server_groups->put($group->getId(), $group);
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\ISet $group
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function removeSet(ISet $group)
    {
        if (! in_array($group, $this->getSets()))
            return;

        try {
            TeamspeakClient::getInstance()->removeSpeakerFromServerGroup($this, $group);
        } catch (TeamspeakException $e) {
            logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
            throw new DriverException($e->getMessage(), $e->getCode(), $e);
        }

        $this->server_groups->pull($group->getId());
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function hydrate(array $attributes)
    {
        $this->id        = array_key_exists('cldbid', $attributes) ?
            $attributes['cldbid'] : $attributes['client_database_id'];
        $this->unique_id = $attributes['client_unique_identifier'];
        $this->nickname  = $attributes['client_nickname'];

        return $this;
    }
}
