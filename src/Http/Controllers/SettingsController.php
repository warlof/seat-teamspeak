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
use Warlof\Seat\Connector\Teamspeak\Validation\ValidateConfiguration;

class SettingsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Seat\Services\Exceptions\SettingException
     **/
    public function getConfiguration()
    {
        $ts_username = setting('teamspeak_username', true);
        $ts_password = setting('teamspeak_password', true);
        $ts_hostname = setting('teamspeak_hostname', true);
        $ts_server_query = setting('teamspeak_server_query', true);
        $ts_server_voice = setting('teamspeak_server_port', true);
        $green_settings = false;

        if ($ts_username != "" && $ts_password != "" && $ts_hostname != "" && $ts_server_query != "" && $ts_server_voice != "") {
            $green_settings = true;
        }

        $parser = new Parsedown();
        $changelog = $parser->parse($this->getChangelog());

        return view('teamspeak::configuration', compact('changelog', 'green_settings'));
    }

    /**
     * @param ValidateConfiguration $request
     *
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function postConfiguration(ValidateConfiguration $request)
    {
        setting(['teamspeak_username', $request->input('teamspeak-configuration-username')], true);
        setting(['teamspeak_password', $request->input('teamspeak-configuration-password')], true);
        setting(['teamspeak_hostname', $request->input('teamspeak-configuration-hostname')], true);
        setting(['teamspeak_server_query', $request->input('teamspeak-configuration-query')], true);
        setting(['teamspeak_server_port', $request->input('teamspeak-configuration-port')], true);

        if ($request->input('teamspeak-configuration-tags') === null) {
            setting(['teamspeak_tags', ''], true);
        } else {
            setting(['teamspeak_tags', $request->input('teamspeak-configuration-tags')], true);
        }

        return redirect()->back()
            ->with('success', 'The Teamspeak settings has been updated');
    }

    /**
     * @param $command_name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getSubmitJob($command_name)
    {
        $accepted_commands = [
            'teamspeak:groups:update',
            'teamspeak:users:update',
            'teamspeak:logs:clear'
        ];

        if (!in_array($command_name, $accepted_commands)) {
            abort(401);
        }

        Artisan::call($command_name);

        return redirect()->back()
            ->with('success', 'The command has been run.');
    }

    /**
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getChangelog() : string
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
}
