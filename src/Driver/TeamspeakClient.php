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

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Seat\Services\Exceptions\SettingException;
use Warlof\Seat\Connector\Drivers\IClient;
use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\IUser;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Exceptions\DriverException;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;
use Warlof\Seat\Connector\Exceptions\InvalidDriverIdentityException;

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
     * @var \Warlof\Seat\Connector\Drivers\Teamspeak\Fetchers\IFetcher
     */
    private $client;

    /**
     * @var int
     */
    private $instance_id;

    /**
     * @var int
     */
    private $server_port;

    /**
     * @var string
     */
    private $api_base_uri;

    /**
     * @var string
     */
    private $api_key;

    /**
     * TeamspeakClient constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        $this->server_port    = $parameters['server_port'];
        $this->instance_id    = $parameters['instance_id'] ?? 0;
        $this->api_base_uri   = $parameters['api_base_uri'];
        $this->api_key        = $parameters['api_key'];

        $this->speakers      = collect();
        $this->server_groups = collect();

        $fetcher = config('teamspeak.config.fetcher');
        $this->client = new $fetcher($this->api_base_uri, $this->api_key);
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public static function getInstance(): IClient
    {
        if (! isset(self::$instance)) {
            try {
                $settings = setting('seat-connector.drivers.teamspeak', true);
            } catch (SettingException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }

            if (is_null($settings) || ! is_object($settings))
                throw new DriverSettingsException('The Driver has not been configured yet.');

            if (! property_exists($settings, 'server_host') || empty($settings->server_host))
                throw new DriverSettingsException('Parameter server_host is missing.');

            if (! property_exists($settings, 'server_port') || is_null($settings->server_port) || $settings->server_port == 0)
                throw new DriverSettingsException('Parameter server_port is missing.');

            if (! property_exists($settings, 'api_base_uri') || empty($settings->api_base_uri))
                throw new DriverSettingsException('Parameter api_base_uri is missing.');

            if (! property_exists($settings, 'api_key') || empty($settings->api_key))
                throw new DriverSettingsException('Parameter api_key is missing.');

            if (! property_exists($settings, 'instance_id') || is_null($settings->instance_id) || $settings->instance_id == 0)
                throw new DriverSettingsException('Parameter instance_id is missing.');

            self::$instance = new TeamspeakClient([
                'server_port'  => $settings->server_port,
                'instance_id'  => $settings->instance_id ?? 0,
                'api_base_uri' => $settings->api_base_uri,
                'api_key'      => $settings->api_key,
            ]);
        }

        return self::$instance;
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\IUser[]
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function getUsers(): array
    {
        if ($this->speakers->isEmpty()) {
            try {
                $this->seedSpeakers();
            } catch (TeamspeakException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d: %s', $e->getCode(), $e->getMessage()));
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->speakers->toArray();
    }

    /**
     * @return \Warlof\Seat\Connector\Drivers\ISet[]
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function getSets(): array
    {
        if ($this->server_groups->isEmpty()) {
            try {
                $this->seedServerGroups();
            } catch (TeamspeakException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->server_groups->toArray();
    }

    /**
     * @param string $id
     * @return \Warlof\Seat\Connector\Drivers\IUser|null
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     * @throws \Warlof\Seat\Connector\Exceptions\InvalidDriverIdentityException
     */
    public function getUser(string $id): ?IUser
    {
        if ($this->speakers->isEmpty()) {
            try {
                $this->seedSpeakers();
            } catch (TeamspeakException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        $user = $this->speakers->get($id);

        if (is_null($user)) {
            try {
                // scope: manage_scope
                $response = $this->sendCall('GET', '/{instance}/clientdbinfo', [
                    'cldbid' => $id,
                    'instance' => $this->instance_id,
                ]);

                $client_info = Arr::first($response);

                $speaker = new TeamspeakSpeaker([
                    'client_database_id' => $client_info->client_database_id,
                    'client_unique_identifier' => $client_info->client_unique_identifier,
                    'client_nickname' => $client_info->client_nickname,
                ]);

                $this->speakers->put($speaker->getClientId(), $speaker);
            } catch (TeamspeakException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));

                if ($e->getCode() == 512)
                    throw new InvalidDriverIdentityException(
                        sprintf('User ID %s is not found on Teamspeak Server.', $id),
                        $e->getCode(),
                        $e);

                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $user;
    }

    /**
     * @param string $nickname
     * @return \Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakSpeaker
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException
     * @throws \Warlof\Seat\Connector\Exceptions\InvalidDriverIdentityException
     */
    public function findUserByName(string $nickname)
    {
        try {
            // scope: manage_scope
            $response = $this->sendCall('GET', '/{instance}/clientdbfind', [
                'pattern' => $nickname,
                'instance' => $this->instance_id,
            ]);

            $id = Arr::first($response)->cldbid;

            // scope: manage_scope
            $response = $this->sendCall('GET', '/{instance}/clientdbinfo', [
                'cldbid' => $id,
                'instance' => $this->instance_id,
            ]);
        } catch (TeamspeakException $e) {
            if ($e->getCode() == 1281)
                throw new InvalidDriverIdentityException(
                    sprintf('Unable to find user %s', $nickname),
                    $e->getCode(),
                    $e);

            if ($e->getCode() == 512)
                throw new InvalidDriverIdentityException(
                    sprintf('Unable to find user with Client ID %d', $id),
                    $e->getCode(),
                    $e);

            throw $e;
        }

        $identity = Arr::first($response);

        $speaker = new TeamspeakSpeaker([
            'client_database_id'       => $identity->client_database_id,
            'client_unique_identifier' => $identity->client_unique_identifier,
            'client_nickname'          => $identity->client_nickname,
        ]);

        return $speaker;
    }

    /**
     * @param string $id
     * @return \Warlof\Seat\Connector\Drivers\ISet|null
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function getSet(string $id): ?ISet
    {
        if ($this->server_groups->isEmpty()) {
            try {
                $this->seedServerGroups();
            } catch (TeamspeakException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
                throw new DriverException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->server_groups->get($id);
    }

    /**
     * @param int $server_port
     * @return int
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    public function findInstanceIdByServerPort(int $server_port): int
    {
        // scope: manage_scope
        $response = $this->sendCall('GET', '/serverlist');

        $instances = collect($response);

        $instance = $instances->first(function ($instance) use ($server_port) {
            return intval($instance->virtualserver_port) == $server_port;
        });

        if (! $instance)
            throw new ServerException(sprintf('Unable to find a server instance listening on port %d.', $server_port));

        return $instance->virtualserver_id;
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IUser $speaker
     * @param \Warlof\Seat\Connector\Drivers\ISet $server_group
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException
     */
    public function addSpeakerToServerGroup(IUser $speaker, ISet $server_group)
    {
        // scope: manage_scope
        $this->sendCall('POST', '/{instance}/servergroupaddclient', [
            'sgid'     => $server_group->getId(),
            'cldbid'   => $speaker->getClientId(),
            'instance' => $this->instance_id,
        ]);
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IUser $speaker
     * @param \Warlof\Seat\Connector\Drivers\ISet $server_group
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException
     */
    public function removeSpeakerFromServerGroup(IUser $speaker, ISet $server_group)
    {
        // scope: manage_scope
        $this->sendCall('POST', '/{instance}/servergroupdelclient', [
            'sgid'     => $server_group->getId(),
            'cldbid'   => $speaker->getClientId(),
            'instance' => $this->instance_id,
        ]);
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\ISet $server_group
     * @return IUser[]
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    public function getServerGroupMembers(ISet $server_group): array
    {
        // scope: manage_scope
        $response = $this->sendCall('GET', '/{instance}/servergroupclientlist', [
            'sgid'     => $server_group->getId(),
            'instance' => $this->instance_id,
        ]);

        $speakers = [];

        foreach ($response as $element) {
            $speakers[] = $this->getUser($element->cldbid);
        }

        return $speakers;
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IUser $speaker
     * @return ISet[]
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     */
    public function getSpeakerServerGroups(IUser $speaker): array
    {
        // scope: manage_scope
        $response = $this->sendCall('GET', '/{instance}/serverinfo', [
            'instance' => $this->instance_id,
        ]);

        $server_info = Arr::first($response);

        // scope: manage_scope
        $response = $this->sendCall('GET', '/{instance}/servergroupsbyclientid', [
            'cldbid' => $speaker->getClientId(),
            'instance' => $this->instance_id,
        ]);

        $server_group = [];

        foreach ($response as $element) {

            // ignore default server group - since it's automatically assigned
            if ($element->sgid == $server_info->virtualserver_default_server_group)
                continue;

            $server_group[] = new TeamspeakServerGroup([
                'sgid' => $element->sgid,
                'name' => $element->name,
            ]);
        }

        return $server_group;
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array $arguments
     * @return array
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     */
    private function sendCall(string $method, string $endpoint, array $arguments = []): array
    {
        $uri = ltrim($endpoint, '/');
        $method = strtoupper($method);

        foreach ($arguments as $uri_parameter => $value) {
            if (strpos($uri, sprintf('{%s}', $uri_parameter)) === false)
                continue;

            $uri = str_replace(sprintf('{%s}', $uri_parameter), $value, $uri);

            Arr::pull($arguments, $uri_parameter);
        }

        try {
            if ($method == 'GET') {
                $response = $this->client->request($method, $uri, [
                    'query' => $arguments,
                ]);
            } else {
                $response = $this->client->request($method, $uri, [
                    'body' => json_encode($arguments),
                ]);
            }

            logger()->debug(
                sprintf('[seat-connector][teamspeak] [http %d, %s] %s -> /%s',
                    $response->getStatusCode(), $response->getReasonPhrase(), $method, $uri),
                $method == 'GET' ? [
                    'response' => [
                        'body' => $response->getBody()->getContents(),
                    ],
                ] : [
                    'request' => [
                        'body' => json_encode($arguments),
                    ],
                    'response' => [
                        'body' => $response->getBody()->getContents(),
                    ],
                ],
            );
        } catch (ConnectException $e) {
            throw new ConnexionException($e->getMessage(), $e->getCode(), $e);
        } catch (RequestException $e) {
            throw new ServerException($e->getMessage(), $e->getCode(), $e);
        }

        $result = json_decode($response->getBody());

        if ($result->status->code !== 0) {
            if (in_array($result->status->code, [5122, 5124]))
                throw new LoginException($result->status->message, $result->status->code);

            throw new CommandException($result->status->message, $result->status->code);
        }

        return $result->body ?? [];
    }

    /**
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    private function seedSpeakers()
    {
        $from        = 0;

        while (true) {
            try {
                // scope: manage_scope
                $response = $this->sendCall('GET', '/{instance}/clientdblist', [
                    'start' => $from,
                    'instance' => $this->instance_id,
                ]);

                foreach ($response as $identity) {
                    $speaker = new TeamspeakSpeaker([
                        'cldbid' => $identity->cldbid,
                        'client_unique_identifier' => $identity->client_unique_identifier,
                        'client_nickname' => $identity->client_nickname,
                    ]);

                    $this->speakers->put($speaker->getClientId(), $speaker);
                    $from++;
                }
            } catch (TeamspeakException $e) {
                if ($e->getCode() == 1281)
                    break;

                throw $e;
            }
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
        // scope: manage_scope
        $response = $this->sendCall('GET', '/{instance}/serverinfo', [
            'instance' => $this->instance_id,
        ]);

        $server_info = Arr::first($response);

        // scope: manage_scope
        $response = $this->sendCall('GET', '/{instance}/servergrouplist', [
            'instance' => $this->instance_id,
        ]);

        foreach ($response as $group) {

            // ignore default server group - since it's automatically assigned
            if ($group->sgid == $server_info->virtualserver_default_server_group)
                continue;

            // groupDbType (0 = template, 1 = normal, 2 = query)
            if ($group->type != '1')
                continue;

            $server_group = new TeamspeakServerGroup([
                'sgid' => $group->sgid,
                'name' => $group->name,
            ]);

            $this->server_groups->put($server_group->getId(), $server_group);
        }
    }
}
