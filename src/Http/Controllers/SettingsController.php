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

use Illuminate\Http\Request;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;

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
    public function store(Request $request)
    {
        $request->validate([
            'server_host'    => 'required|string',
            'server_port'    => 'required|numeric|min:1|max:65535',
            'api_base_uri'   => 'required|url',
            'api_key'        => 'required|string',
        ]);

        $old_settings = setting('seat-connector.drivers.teamspeak', true) ?? null;

        $settings = [
            'server_host'  => $request->input('server_host'),
            'server_port'  => (int) $request->input('server_port'),
            'api_base_uri' => $request->input('api_base_uri'),
            'api_key'      => $request->input('api_key'),
        ];

        try {
            setting(['seat-connector.drivers.teamspeak', (object) $settings], true);

            $settings['instance_id'] = $this->findServerInstance($settings);
        } catch (TeamspeakException $e) {
            setting(['seat-connector.drivers.teamspeak', $old_settings], true);

            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        setting(['seat-connector.drivers.teamspeak', (object) $settings], true);

        return redirect()->back()
            ->with('success', 'Teamspeak settings has been successfully saved.');
    }

    /**
     * @param array $settings
     * @return int
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    private function findServerInstance(array $settings): int
    {
        $client = new TeamspeakClient($settings);

        return $client->findInstanceIdByServerPort($settings['server_port']);
    }
}
