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

namespace Warlof\Seat\Connector\Drivers\Teamspeak\Commands;

use Illuminate\Console\Command;
use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;

/**
 * Class Upgrade.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Commands
 */
class Upgrade extends Command
{
    protected $signature = 'seat-connector:upgrade:teamspeak';

    protected $description = 'Process data migration from Teamspeak 3.x generation to 4.x';

    public function handle()
    {
        $server_host = $this->ask('What is either the IP address or domain name where your Teamspeak instance is ?', 'localhost');
        $server_port = intval($this->ask('What is the client port from your Teamspeak instance ?', 9987));
        $query_port = intval($this->ask('What is the query port from your Teamspeak server ?', 10011));
        $query_username = $this->ask('What is the server query username from your Teamspeak server ?', 'serveradmin');
        $query_password = $this->secret('What is the server query password from your Teamspeak server ?');

        if (in_array(null, [$server_host, $server_port, $query_port, $query_username, $query_password]))
            $this->error('You must provide a value for all parameters.');

        if (! is_string($server_host))
            $this->error('Server Host must be a valid domain or IP address');

        if ($server_port == 0 || $server_port < 1 || $server_port > 65535)
            $this->error('Server Port must be a valid port number');

        if ($query_port == 0 || $query_port < 1 || $query_port > 65535)
            $this->error('Query Port must be a valid port number');

        if (empty($query_username) || ! is_string($query_username))
            $this->error('Query Username must be a valid string');

        if (empty($query_password) || ! is_string($query_password))
            $this->error('Query Password must be a valid string');

        $settings = [
            'server_host'    => $server_host,
            'server_port'    => (int) $server_port,
            'query_port'     => (int) $query_port,
            'query_username' => $query_username,
            'query_password' => $query_password,
        ];

        setting(['seat-connector.drivers.teamspeak', (object) $settings], true);

        // attempt to connect to Teamspeak and pull servers groups
        try {
            $client = TeamspeakClient::getInstance();
            $client->getSets();
        } catch (TeamspeakException $e) {
            $this->error('Unable to connect to the Teamspeak instance');
        }
    }
}
