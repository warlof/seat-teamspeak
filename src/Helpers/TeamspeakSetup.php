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

namespace Warlof\Seat\Connector\Teamspeak\Helpers;


use TeamSpeak3;
use TeamSpeak3_Node_Server;
use Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException;

class TeamspeakSetup
{
    const SERVER_HOSTNAME_KEY = 'warlof.teamspeak-connector.server.hostname';
    const SERVER_INSTANCE_PORT_KEY = 'warlof.teamspeak-connector.server.instance.port';
    const SERVER_QUERY_PASSWORD_KEY = 'warlof.teamspeak-connector.server.query.password';
    const SERVER_QUERY_PORT_KEY = 'warlof.teamspeak-connector.server.query.port';
    const SERVER_QUERY_USERNAME_KEY = 'warlof.teamspeak-connector.server.query.username';

    /**
     * @var string
     */
    private $query_nickname;

    /**
     * @var array
     */
    private $settings = [
        'username' => null,
        'password' => null,
        'hostname' => null,
        'query_port' => null,
        'instance_port' => null,
    ];

    /**
     * @var \TeamSpeak3_Node_Server
     */
    private $teamspeak;

    /**
     * TeamspeakSetup constructor.
     * @throws TeamspeakSettingException
     */
    public function __construct()
    {
        $this->settings = [
            'hostname' => setting(self::SERVER_HOSTNAME_KEY, true),
            'instance_port' => setting(self::SERVER_INSTANCE_PORT_KEY, true),
            'password' => setting(self::SERVER_QUERY_PASSWORD_KEY, true),
            'query_port' => setting(self::SERVER_QUERY_PORT_KEY, true),
            'username' => setting(self::SERVER_QUERY_USERNAME_KEY, true),
        ];

        // generating a unique ID prefixed by SeAT with 23 characters length (bump to 28)
        $this->query_nickname = uniqid('SeAT_', true);

        logger()->debug(sprintf('Spawning a new %s instance with UID %s', self::class, $this->query_nickname));

        $this->validateSettings();
    }

    /**
     * Ensure all required settings have been set.
     *
     * @throws TeamspeakSettingException
     */
    private function validateSettings()
    {
        if (is_null($this->settings['username']) || empty($this->settings['username']))
            throw new TeamspeakSettingException('Teamspeak username has not been set.');

        if (is_null($this->settings['password']) || empty($this->settings['password']))
            throw new TeamspeakSettingException('Teamspeak password has not been set.');

        if (is_null($this->settings['hostname']) || empty($this->settings['hostname']))
            throw new TeamspeakSettingException('Teamspeak hostname has not been set.');

        if (is_null($this->settings['instance_port']) || empty($this->settings['instance_port']))
            throw new TeamspeakSettingException('Teamspeak instance port has not been set.');

        if (is_null($this->settings['query_port']) || empty($this->settings['query_port']))
            throw new TeamspeakSettingException('Teamspeak query port has not been set.');
    }

    /**
     * @return TeamSpeak3_Node_Server
     * @throws TeamspeakSettingException
     */
    public function getInstance(): TeamSpeak3_Node_Server
    {
        if (is_null($this->teamspeak)) {

            // preparing server query URI to which open a socket
            $uri = sprintf('serverquery://%s:%s@%s:%s/?server_port=%s&blocking=0&nickname=%s',
                $this->settings['username'],
                $this->settings['password'],
                $this->settings['hostname'],
                $this->settings['query_port'],
                $this->settings['instance_port'],
                $this->query_nickname);

            // building a Teamspeak server instance
            $this->teamspeak = TeamSpeak3::factory($uri);
        }

        return $this->teamspeak;
    }

    public function getQueryNickname(): string
    {
        return $this->query_nickname;
    }
}
