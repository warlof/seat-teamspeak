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

namespace Warlof\Seat\Connector\Drivers\Teamspeak\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Connector\Drivers\ISet;
use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\InvalidServerGroupException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Http\Validation\Settings;

/**
 * Class SettingsController.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Http\Controllers
 */
class SettingsController extends Controller
{
    /**
     * @param \Illuminate\Support\Facades\Request $request
     */
    public function store(Settings $request)
    {
        $settings = [
            'server_host'             => $request->input('server_host'),
            'server_port'             => (int) $request->input('server_port'),
            'api_base_uri'            => $request->input('api_base_uri'),
            'api_key'                 => $request->input('api_key'),
            'registration_group_id'   => 0,
            'registration_group_name' => $request->input('registration_group_name'),
        ];

        try {
            $settings = $this->verify($settings);
        } catch (TeamspeakException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        setting(['seat-connector.drivers.teamspeak', (object) $settings], true);

        return redirect()->back()
            ->with('success', 'Teamspeak settings has been successfully saved.');
    }

    /**
     * Verify provided settings and return updated settings in case of success.
     *
     * @param array $settings
     *
     * @return array
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\CommandException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\InvalidServerGroupException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     * @throws \Warlof\Seat\Connector\Exceptions\DriverException
     */
    private function verify(array $settings)
    {
        $client = new TeamspeakClient($settings);

        $settings['instance_id'] = $client->findInstanceIdByServerPort($settings['server_port']);

        // attempt to retrieve server group ID matching with provided name
        $group = collect($client->setInstance($settings['instance_id'])->getSets())
            ->first(function (ISet $server_group) use ($settings) {
                return $server_group->getName() == $settings['registration_group_name'];
            });

        if (is_null($group))
            throw new InvalidServerGroupException($settings['registration_group_name']);

        // update setting container with registration group ID
        $settings['registration_group_id']   = $group->getId();
        $settings['registration_group_name'] = $group->getName();

        return $settings;
    }
}
