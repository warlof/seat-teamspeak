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

namespace Warlof\Seat\Connector\Teamspeak\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Artisan;
use Parsedown;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup;
use Warlof\Seat\Connector\Teamspeak\Validation\ValidateConfiguration;

class SettingsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Seat\Services\Exceptions\SettingException
     **/
    public function getConfiguration()
    {
        $parser = new Parsedown();
        $changelog = $parser->parse($this->getChangelog());

        return view('teamspeak::configuration', compact('changelog'));
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getChangelog(): string
    {
        try {
            $response = (new Client())
                ->request('GET', 'https://raw.githubusercontent.com/warlof/seat-teamspeak/master/CHANGELOG.md');

            if ($response->getStatusCode() != 200) {
                return 'Error while fetching changelog';
            }

            $parser = new Parsedown();
            return $parser->parse($response->getBody());
        } catch (RequestException $e) {
            return 'Error while fetching changelog';
        }
    }

    /**
     * @param ValidateConfiguration $request
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function postConfiguration(ValidateConfiguration $request)
    {
        setting([TeamspeakSetup::SERVER_HOSTNAME_KEY, $request->input('teamspeak-configuration-hostname')], true);
        setting([TeamspeakSetup::SERVER_INSTANCE_PORT_KEY, $request->input('teamspeak-configuration-port')], true);
        setting([TeamspeakSetup::SERVER_QUERY_PASSWORD_KEY, $request->input('teamspeak-configuration-password')], true);
        setting([TeamspeakSetup::SERVER_QUERY_PORT_KEY, $request->input('teamspeak-configuration-query')], true);
        setting([TeamspeakSetup::SERVER_QUERY_USERNAME_KEY, $request->input('teamspeak-configuration-username')], true);

        if ($request->input('teamspeak-configuration-tags') === null) {
            setting(['warlof.teamspeak-connector.tags', false], true);
        } else {
            setting(['warlof.teamspeak-connector.tags', (bool)$request->input('teamspeak-configuration-tags')], true);
        }

        return redirect()->back()
            ->with('success', 'The Teamspeak settings has been updated');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postSubmitJob()
    {
        $accepted_commands = [
            'teamspeak:group:sync',
            'teamspeak:user:policy',
            'teamspeak:logs:clear'
        ];

        $command = request()->input('command');
        $parameters = request()->input('parameters');

        if (!in_array($command, $accepted_commands)) {
            abort(400);
        }

        Artisan::call($command, $parameters ?: []);

        return redirect()->back()
            ->with('success', 'The command has been run.');
    }
}
