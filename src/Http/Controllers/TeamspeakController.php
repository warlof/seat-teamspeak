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

use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Services\Models\UserSetting;
use Seat\Web\Http\Controllers\Controller;
use TeamSpeak3_Adapter_ServerQuery_Exception;
use Warlof\Seat\Connector\Teamspeak\Exceptions\MissingMainCharacterException;
use Warlof\Seat\Connector\Teamspeak\Helpers\TeamspeakSetup;
use Warlof\Seat\Connector\Teamspeak\Jobs\TeamspeakUserOrchestrator;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakUser;

class TeamspeakController extends Controller
{
    public function getUsers()
    {
        if (! request()->ajax())
            return view('teamspeak::users.list');

        $teamspeak_users = TeamspeakUser::query()
            ->leftJoin((new UserSetting())->getTable(), function ($join) {
                $join->on((new TeamspeakUser())->getTable() . '.group_id', '=', (new UserSetting())->getTable() . '.group_id')
                    ->where((new UserSetting())->getTable() . '.name', '=', 'main_character_id');
            })
            ->leftJoin((new CharacterInfo())->getTable(), 'character_id', '=', 'value')
            ->select(
                (new TeamspeakUser())->getTable() . '.*',
                (new UserSetting())->getTable() . '.value AS user_id',
                (new CharacterInfo())->getTable() . '.name as user_name'
            );

        return app('DataTables')::of($teamspeak_users)
            ->make(true);
    }

    public function postRemoveUserMapping()
    {
        $teamspeak_id = request()->input('teamspeak_id');

        if ($teamspeak_id == '')
            return redirect()->back('error', 'An error occurred while processing the request.');

        if (is_null($teamspeak_user = TeamspeakUser::where('teamspeak_id', $teamspeak_id)->first()))
            return redirect()->back()->with('error', sprintf('System cannot find any suitable mapping for Teamspeak (%s).', $teamspeak_id));

        $teamspeak_user->delete();

        return redirect()->back()->with('success',
            sprintf('System sucessfully remove the mapping between SeAT (%s) and Teamspeak (%s)',
                optional($teamspeak_user->group->main_character)->name, $teamspeak_user->teamspeak_id));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\MissingMainCharacterException
     */
    public function getRegisterUser()
    {
        $main_character = auth()->user()->group->main_character;

        if (! $main_character) {
            return redirect('home')->with('error', 'Could not find your Main Character.  Check your Profile for the correct Main.');
        }

        $corporation = CorporationInfo::find($main_character->corporation_id);

        if (! $corporation && setting('warlof.teamspeak-connector.tags', true) === true) {
            return redirect('home')->with('error', 'Could not find your Corporation.  Please have your CEO upload a Corp API key to this website.');
        }

        $teamspeak_username = $this->getTeamspeakFormattedNickname();

        return view('teamspeak::register', compact('teamspeak_username'));
    }

    /**
     * @return bool|string
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\MissingMainCharacterException
     */
    private function getTeamspeakFormattedNickname()
    {
        $main_character = auth()->user()->group->main_character;
        if (is_null($main_character))
            throw new MissingMainCharacterException(auth()->user()->group);

        $teamspeak_name = $main_character->name;

        if (setting('warlof.teamspeak-connector.tags', true) === true) {
            $corp = CorporationInfo::find($main_character->corporation_id);
            $teamspeak_name = sprintf('%s | %s', $corp->ticker, $main_character->name);
        }

        // Teamspeak has a 30 char limit on names. Trim it.
        return substr($teamspeak_name, 0, 30);
    }

    /**
     * @return string
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\MissingMainCharacterException
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     */
    public function postGetUserUid()
    {
        $client = new TeamspeakSetup();
        $group_id = auth()->user()->group_id;

        $user_list = $client->getInstance()->clientList();

        $teamspeak_user = TeamspeakUser::find($group_id);

        // in case we already had an existing binding for the current user, we will drop its access
        if (! is_null($teamspeak_user)) {

            // retrieve user information
            try {
                $user_info = $client->getInstance()->clientGetNameByUid($teamspeak_user->teamspeak_id);
                // retrieve server default group
                $default_sgid = $client->getInstance()->getInfo()['virtualserver_default_server_group'];
                // retrieve user server groups
                $user_groups = $client->getInstance()->clientGetServerGroupsByDbid($user_info['cldbid']);

                foreach ($user_groups as $user_group) {
                    if ($user_group['sgid'] === $default_sgid)
                        continue;

                    $client->getInstance()->serverGroupClientDel($user_group['sgid'], $user_info['cldbid']);
                }
            } catch (TeamSpeak3_Adapter_ServerQuery_Exception $e) {
                // (code: 512) invalid clientID
                if ($e->getCode() !== 512)
                    throw $e;

                $teamspeak_user->delete();
            }
        }

        foreach ($user_list as $user) {

            // escape the nickname returned by Teamspeak
            $nickname = preg_replace('/’/', '\'', $user->client_nickname->toString());

            if ($nickname !== $this->getTeamspeakFormattedNickname())
                continue;

            // extract the UID from the user element
            $uid = $user->client_unique_identifier->toString();

            // update the binding into SeAT
            $teamspeak_user = $this->postRegisterUser($uid);

            // queue a job which will grant server groups to the user
            dispatch(new TeamspeakUserOrchestrator($teamspeak_user))->onQueue('high');

            return response()->json([
                'id' => $uid,
                'nick' => $nickname,
            ]);
        }

        return response()->json([
            'error' => 'Unable to retrieve you on Teamspeak. Ensure you have the proper nickname.',
        ], 404);
    }

    /**
     * @param $uid
     */
    private function postRegisterUser($uid)
    {
        $group_id = auth()->user()->group_id;

        return TeamspeakUser::updateOrCreate(
            ['group_id' => $group_id],
            ['teamspeak_id' => $uid]
        );
    }
}
