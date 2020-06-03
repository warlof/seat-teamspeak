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

use Illuminate\Support\Str;
use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Connector\Drivers\IClient;
use Warlof\Seat\Connector\Drivers\IUser;
use Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient;
use Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions\TeamspeakException;
use Warlof\Seat\Connector\Events\EventLogger;
use Warlof\Seat\Connector\Exceptions\DriverException;
use Warlof\Seat\Connector\Exceptions\DriverSettingsException;
use Warlof\Seat\Connector\Exceptions\InvalidDriverIdentityException;
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

            $registration_nickname = Str::random(30);

            session(['seat-connector.teamspeak.registration_uuid' => $registration_nickname]);
        } catch (DriverSettingsException $e) {
            event(new EventLogger('teamspeak', 'critical', 'registration', $e->getMessage()));
            logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));

            return redirect()->back()
                ->with('error', $e->getMessage());
        }

        $drivers = collect(config('seat-connector.drivers', []));
        $identities = User::where('user_id', auth()->user()->id)->get();

        return view('seat-connector-teamspeak::registrations.confirm',
            compact('drivers', 'identities', 'settings', 'registration_nickname'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function handleProviderCallback()
    {
        // determine the expected nickname for that user
        $searched_nickname = session('seat-connector.teamspeak.registration_uuid');

        try {
            // retrieve the teamspeak client instance
            $client = TeamspeakClient::getInstance();

            // search for the expected user
            $match_user = $client->findUserByName($searched_nickname);
        } catch (InvalidDriverIdentityException $e) {
            logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
            event(new EventLogger('teamspeak', 'critical', 'registration', $e->getMessage()));

            return redirect()->route('seat-connector.identities')
                ->with('error', sprintf('Sorry, but we were not able to find you with nickname %s on the server.', $searched_nickname));
        } catch (DriverException | TeamspeakException $e) {
            logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
            event(new EventLogger('teamspeak', 'critical', 'registration', $e->getMessage()));

            return redirect()->route('seat-connector.identities')
                ->with('error', $e->getMessage());
        }

        $original_user = User::where('connector_type', 'teamspeak')->where('user_id', auth()->user()->id)->first();

        // if connector ID is a new one - revoke existing access from the old ID
        if (! is_null($original_user) && $original_user->connector_id != $match_user->getClientId())
            $this->revokeOldIdentity($client, $original_user);

        // register the user
        $profile = $this->coupleUser(auth()->user()->id, $searched_nickname, $match_user);

        $allowed_sets = $profile->allowedSets();

        foreach ($allowed_sets as $set_id) {
            try {
                $set = $client->getSet($set_id);

                if (is_null($set)) {
                    logger()->error(sprintf('[seat-connector][teamspeak] Unable to retrieve Server Group with ID %s', $set_id));
                    event(new EventLogger('teamspeak', 'error', 'registration',
                        sprintf('Unable to retrieve Server Group with ID %s', $set_id)));

                    continue;
                }

                $match_user->addSet($set);
            } catch (DriverException $e) {
                logger()->error(sprintf('[seat-connector][teamspeak] %d : %s', $e->getCode(), $e->getMessage()));
                event(new EventLogger('teamspeak', 'critical', 'registration', $e->getMessage()));
            }
        }

        return redirect()
            ->route('seat-connector.identities')
            ->with('success', sprintf(
                'You have successfully been registered on Teamspeak. You can now rename yourself to %s',
                $profile->buildConnectorNickname()));
    }

    /**
     * @param int $user_id
     * @param string $nickname
     * @param \Warlof\Seat\Connector\Drivers\IUser $user
     * @return \Warlof\Seat\Connector\Models\User
     */
    private function coupleUser(int $user_id, string $nickname, IUser $user): User
    {
        $profile = User::updateOrCreate([
            'user_id'        => $user_id,
            'connector_type' => 'teamspeak',
        ], [
            'connector_name' => $nickname,
            'connector_id'   => $user->getClientId(),
            'unique_id'      => $user->getUniqueId(),
        ]);

        event(new EventLogger('teamspeak', 'notice', 'registration',
            sprintf('User %s (%d) has been registered with ID %s and UID %s',
                $nickname, $user_id, $user->getClientId(), $user->getUniqueId())));

        return $profile;
    }

    /**
     * @param \Warlof\Seat\Connector\Drivers\IClient $client
     * @param \Warlof\Seat\Connector\Models\User $old_identity
     */
    private function revokeOldIdentity(IClient $client, User $old_identity)
    {
        try {
            // retrieve teamspeak user related to old identity
            $user = $client->getUser($old_identity->connector_id);

            // pull its active sets
            $sets = $user->getSets();

            // revoke all of them
            foreach ($sets as $set) {
                $user->removeSet($set);
            }
        } catch (InvalidDriverIdentityException $e) {
            return;
        }

        // log action
        event(new EventLogger('discord', 'warning', 'registration',
            sprintf('User %s (%d) has been uncoupled from ID %s and UID %s',
                $old_identity->connector_name, $old_identity->user_id, $old_identity->connector_id, $old_identity->unique_id)));
    }
}
