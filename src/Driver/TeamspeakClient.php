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

use ts3admin;
use Warlof\Seat\Connector\Drivers\IClient;
use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\IUser;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;

/**
 * Class TeamspeakClient.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Driver
 */
class TeamspeakClient implements IClient
{
    /**
     * @var \Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient
     */
    private static $instance;

    /**
     * @var \Warlof\Seat\Connector\Drivers\IUser[]
     */
    private $speakers;

    /**
     * @var \Warlof\Seat\Connector\Drivers\ISet[]
     */
    private $server_groups;

    /**
     * @var \ts3admin
     */
    private $client;

    /**
     * @var string
     */
    private $server_host;

    /**
     * @var int
     */
    private $server_port;

    /**
     * @var int
     */
    private $query_port;

    /**
     * @var string
     */
    private $query_username;

    /**
     * @var string
     */
    private $query_password;

    /**
     * TeamspeakClient constructor.
     *
     * @param array $parameters
     */
    private function __construct(array $parameters)
    {
        $this->server_host    = $parameters['server_host'];
        $this->server_port    = $parameters['server_port'];
        $this->query_port     = $parameters['query_port'];
        $this->query_username = $parameters['query_username'];
        $this->query_password = $parameters['query_password'];

        $this->speakers      = collect();
        $this->server_groups = collect();
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverSettingsException
     */
    public static function getInstance(): IClient
    {
        if (! isset(self::$instance)) {
            $settings = setting('seat-connector.drivers.teamspeak', true);

            if (is_null($settings) || ! is_object($settings))
                throw new DriverSettingsException('The Driver has not been configured yet.');

            if (! property_exists($settings, 'server_host') || is_null($settings->server_host) || $settings->server_host == '')
                throw new DriverSettingsException('Parameter server_host is missing.');

            if (! property_exists($settings, 'server_port') || is_null($settings->server_port) || $settings->server_port == 0)
                throw new DriverSettingsException('Parameter server_port is missing.');

            if (! property_exists($settings, 'query_port') || is_null($settings->query_port) || $settings->query_port == 0)
                throw new DriverSettingsException('Parameter query_port is missing.');

            if (! property_exists($settings, 'query_username') || is_null($settings->query_username) || $settings->query_username == '')
                throw new DriverSettingsException('Parameter query_username is missing.');

            if (! property_exists($settings, 'query_password') || is_null($settings->query_password) || $settings->query_password == '')
                throw new DriverSettingsException('Parameter query_password is missing.');

            self::$instance = new TeamspeakClient([
                'server_host'    => $settings->server_host,
                'server_port'    => $settings->server_port,
                'query_port'     => $settings->query_port,
                'query_username' => $settings->query_username,
                'query_password' => $settings->query_password,
            ]);
        }

        return self::$instance;
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\IUser[]
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    public function getUsers(): array
    {
        if ($this->speakers->isEmpty())
            $this->seedSpeakers();

        return $this->speakers->toArray();
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\ISet[]
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     */
    public function getSets(): array
    {
        if ($this->server_groups->isEmpty())
            $this->seedServerGroups();

        return $this->server_groups->toArray();
    }

    /**
     * @param string $id
     * @return \Warlof\Seat\Connector\Drivers\IUser|null
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    public function getUser(string $id): ?IUser
    {
        if ($this->speakers->isEmpty())
            $this->seedSpeakers();

        return $this->speakers->get($id);
    }

    /**
     * @param string $id
     * @return \Warlof\Seat\Connector\Drivers\ISet|null
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     */
    public function getSet(string $id): ?ISet
    {
        if ($this->server_groups->isEmpty())
            $this->seedServerGroups();

        return $this->server_groups->get($id);
    }

    /**
     * @param string $command
     * @param array $arguments
     * @return mixed
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    public function sendCall(string $command, array $arguments = [])
    {
        $this->connect();
        $response = call_user_func_array([$this->client, $command], $arguments);

        if (! $this->client->succeeded($response))
            throw new CommandException($response['errors']);

        return $response;
    }

    /**
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    private function connect()
    {
        if ($this->isConnected())
            return;

        $this->client = new ts3admin($this->server_host, $this->query_port, 15);

        $response = $this->client->connect();
        if (! $this->client->succeeded($response))
            throw new ConnexionException($response['errors']);

        $response = $this->client->login($this->query_username, $this->query_password);
        if (! $this->client->succeeded($response))
            throw new LoginException($response['errors']);

        $response = $this->client->selectServer($this->server_port);
        if (! $this->client->succeeded($response))
            throw new ServerException($response['errors']);
    }

    /**
     * @return bool
     */
    private function isConnected(): bool
    {
        if (is_null($this->client))
            return false;

        return $this->client->succeeded($this->client->connect());
    }

    /**
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    private function seedSpeakers()
    {
        // ensure we have an open socket to the server
        if (! $this->isConnected())
            $this->connect();

        $speakers = $this->client->clientDbList();
        if (! $this->client->succeeded($speakers))
            throw new CommandException($speakers['errors']);

        foreach ($speakers['data'] as $speaker_attributes) {
            $speaker = new TeamspeakSpeaker($speaker_attributes);
            $this->speakers->put($speaker->getClientId(), $speaker);
        }
    }

    /**
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     */
    private function seedServerGroups()
    {
        // ensure we have an open socket to the server
        if (! $this->isConnected())
            $this->connect();

        $server_info = $this->client->serverInfo();
        if (! $this->client->succeeded($server_info))
            throw new CommandException($server_info['errors']);

        // collect only normal server from the active instance
        $server_groups = $this->client->serverGroupList(1);
        if (! $this->client->succeeded($server_groups))
            throw new ConnexionException($server_groups['errors']);

        foreach ($server_groups['data'] as $group_attributes) {

            // ignore default server group
            if ($group_attributes['sgid'] == $server_info['data']['virtualserver_default_server_group'])
                continue;

            $server_group = new TeamspeakServerGroup($group_attributes);
            $this->server_groups->put($server_group->getId(), $server_group);
        }
    }
}
