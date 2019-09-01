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
use Warlof\Seat\Connector\Drivers\IUser;
use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Events\EventLogger;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;
use Warlof\Seat\Connector\Models\User;

/**
 * Class RegistrationController.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Http\Controllers
 */
class RegistrationController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\View\View
     * @throws \Seat\Services\Exceptions\SettingException
     */
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
            event(new EventLogger('teamspeak', 'critical', 'registration', $e->getMessage()));
            logger()->error($e->getMessage());

            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        $drivers = collect(config('seat-connector.drivers', []));
        $identities = User::where('group_id', auth()->user()->group_id)->get();

        return view('seat-connector-teamspeak::registrations.confirm',
            compact('drivers', 'identities', 'settings', 'registration_nickname'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Seat\Services\Exceptions\SettingException
     */
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

            // collect all known users
            $users = collect($client->getUsers());

            // search valid active user
            $found_user = array_first(array_get($client->sendCall('clientdbfind', [$searched_nickname]), 'data'));

            // search for the expected user
            $match_user = $users->filter(function ($user) use ($found_user) {
                return $user->getClientId() == $found_user['cldbid'];
            });

        } catch (DriverSettingsException | TeamspeakException $e) {
            logger()->error($e->getMessage());
            event(new EventLogger('teamspeak', 'critical', 'registration', $e->getMessage()));

            return redirect()->route('seat-connector.identities')
                ->with('error', $e->getMessage());
        }

        // in case no user can be found with that nickname - display an error
        if ($match_user->isEmpty()) {
            logger()->error(sprintf('Unable to retrieve user with name %s', $searched_nickname));
            event(new EventLogger('teamspeak', 'warning', 'registration',
                sprintf('Unable to retrieve user with name %s', $searched_nickname)));

            return redirect()->back()
                ->with('error', 'Unable to retrieve you on the server - is your nickname valid ?');
        }

        $match_user = $match_user->last();

        // register the user
        $profile = $this->coupleUser(auth()->user()->group_id, $searched_nickname, $match_user);

        $allowed_sets = $profile->allowedSets();

        foreach ($allowed_sets as $set_id) {
            try {
                $set = $client->getSet($set_id);

                if (is_null($set)) {
                    logger()->error(sprintf('Unable to retrieve Server Group with ID %s', $set_id));
                    event(new EventLogger('teamspeak', 'error', 'registration',
                        sprintf('Unable to retrieve Server Group with ID %s', $set_id)));

                    continue;
                }

                $match_user->addSet($set);
            } catch (TeamspeakException $e) {
                logger()->error($e->getMessage());
                event(new EventLogger('teamspeak', 'critical', 'registration', $e->getMessage()));
            }
        }

        return redirect()
            ->route('seat-connector.identities')
            ->with('success', 'You have successfully been registered on Teamspeak.');
    }

    /**
     * @param int $group_id
     * @param string $nickname
     * @param \Warlof\Seat\Connector\Drivers\IUser $user
     * @return \Warlof\Seat\Connector\Models\User
     */
    private function coupleUser(int $group_id, string $nickname, IUser $user): User
    {
        $profile = User::updateOrCreate([
            'group_id'       => $group_id,
            'connector_type' => 'teamspeak',
        ], [
            'connector_name' => $nickname,
            'connector_id'   => $user->getClientId(),
            'unique_id'      => $user->getUniqueId(),
        ]);

        event(new EventLogger('teamspeak', 'notice', 'registration',
            sprintf('User %s (%d) has been registered with ID %s and UID %s',
                $nickname, $group_id, $user->getClientId(), $user->getUniqueId())));

        return $profile;
    }
}
