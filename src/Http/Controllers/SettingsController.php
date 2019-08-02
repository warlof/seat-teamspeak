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
use ts3admin;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException;
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
            'query_port'     => 'required|numeric|min:1|max:65535',
            'query_username' => 'required|string',
            'query_password' => 'required|string',
        ]);

        $settings = [
            'server_host'    => $request->input('server_host'),
            'server_port'    => (int) $request->input('server_port'),
            'query_port'     => (int) $request->input('query_port'),
            'query_username' => $request->input('query_username'),
            'query_password' => $request->input('query_password'),
        ];

        try {
            $this->checkSettings($settings);
        } catch (TeamspeakException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        setting(['seat-connector.drivers.teamspeak', $settings], true);

        return redirect()->back()
            ->with('success', 'Teamspeak settings has been successfully saved.');
    }

    /**
     * @param array $settings
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ConnexionException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\LoginException
     * @throws \Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\ServerException
     */
    private function checkSettings(array $settings)
    {
        $client = new ts3admin($settings['server_host'], $settings['server_port'], 15);

        $response = $client->connect();
        if (! $client->succeeded($response))
            throw new ConnexionException($response['errors']);

        $response = $client->login($settings['query_username'], $settings['query_password']);
        if (! $client->succeeded($response))
            throw new LoginException($response['errors']);

        $response = $client->selectServer($settings['server_port']);
        if (! $client->succeeded($response))
            throw new ServerException($response['errors']);
    }
}
