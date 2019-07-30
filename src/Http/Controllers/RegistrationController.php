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

use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;
use Warlof\Seat\Connector\Models\User;

class RegistrationController
{
    public function redirectToProvider()
    {
        try {
            $settings = setting('seat-connector.drivers.teamspeak', true);

            if (is_null($settings) || !is_object($settings))
                throw new DriverSettingsException('The Driver has not been configured yet.');

            if (! property_exists($settings, 'server_host') || is_null($settings->server_host))
                throw new DriverSettingsException('Parameter server_host is missing.');

            if (! property_exists($settings, 'server_port') || is_null($settings->server_port))
                throw new DriverSettingsException('Parameter server_port is missing.');

            // build a draft connector user in order to determine the expected nickname
            $driver_user = new User([
                'group_id' => auth()->user()->group_id,
                'connector_type' => 'teamspeak',
            ]);

            $registration_nickname = $driver_user->buildConnectorNickname();
        } catch (DriverSettingsException $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        $drivers = collect(config('seat-connector.drivers', []));
        $identities = User::where('group_id', auth()->user()->group_id)->get();

        return view('seat-connector-teamspeak::registrations.confirm',
            compact('drivers', 'identities', 'settings', 'registration_nickname'));
    }

    public function handleProviderCallback()
    {
        // build a draft connector user in order to determine the expected nickname
        $driver_user = new User([
            'group_id' => auth()->user()->group_id,
            'connector_type' => 'teamspeak',
        ]);

        // determine the expected nickname for that user
        $searched_nickname = $driver_user->buildConnectorNickname();

        try {

            // retrieve the teamspeak client instance
            $client = TeamspeakClient::getInstance();

            // collect all active users
            $users = collect($client->getUsers());

            // search for the expected user
            $match_user = $users->filter(function ($user) use ($searched_nickname) {

                return $user->getName() == $searched_nickname;
            });

        } catch (DriverSettingsException | TeamspeakException $e) {
            return redirect()->route('seat-connector.identities')
                ->with('error', $e->getMessage());
        }

        // in case no user can be found with that nickname - display an error
        if ($match_user->isEmpty())
            return redirect()->back()
                ->with('error', 'Unable to retrieve you on the server - is your nickname valid ?');

        $match_user = $match_user->first();

        // register the user
        User::updateOrCreate([
            'group_id'       => auth()->user()->group_id,
            'connector_type' => 'teamspeak',
        ], [
            'connector_name' => $searched_nickname,
            'connector_id'   => $match_user->getClientId(),
            'unique_id'      => $match_user->getUniqueId(),
        ]);

        return redirect()
            ->route('seat-connector.identities')
            ->with('success', 'You have successfully been registered on Teamspeak.');
    }
}
