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

namespace Seat\Warlof\Teamspeak\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException;
use Seat\Web\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Parsedown;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Warlof\Teamspeak\Models\TeamspeakUser;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroup;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupPublic;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupUser;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupRole;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupCorporation;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupAlliance;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupTitle;
use Seat\Warlof\Teamspeak\Models\TeamspeakLog;
use Seat\Warlof\Teamspeak\Validation\AddRelation;
use Seat\Warlof\Teamspeak\Validation\ValidateConfiguration;
use Seat\Warlof\Teamspeak\Helpers\TeamspeakHelper;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;
use TeamSpeak3_Node_Client;

class TeamspeakController extends Controller
{

    private $teamspeak;

    public function getRelations()
    {
        $group_public = TeamspeakGroupPublic::all();
        $group_users = TeamspeakGroupUser::all();
        $group_roles = TeamspeakGroupRole::all();
        $group_corporations = TeamspeakGroupCorporation::all();
        $group_alliances = TeamspeakGroupAlliance::all();
        $group_titles = TeamspeakGroupTitle::all();
        
        $users = User::all();
        $roles = Role::all();
        $corporations = CorporationInfo::all();
        $alliances = Alliance::all();
        $groups = TeamspeakGroup::all();
		
        return view('teamspeak::list',
            compact('group_public', 'group_users', 'group_roles', 'group_corporations', 'group_alliances', 'group_titles',
                'users', 'roles', 'corporations', 'alliances', 'groups'));
    }

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
    
    public function getLogs()
    {
        $logs = TeamspeakLog::orderBy('created_at', 'desc')->take(30)->get();

        return view('teamspeak::logs', compact('logs'));
    }

    public function postRelation(AddRelation $request)
    {
        $user_id = $request->input('teamspeak-user-id');
        $role_id = $request->input('teamspeak-role-id');
        $corporation_id = $request->input('teamspeak-corporation-id');
        $alliance_id = $request->input('teamspeak-alliance-id');
        $title_id = $request->input('teamspeak-title-id');
        $group_id = $request->input('teamspeak-group-id');

        // use a single post route in order to create any kind of relation
        // value are user, role, corporation or alliance
        switch ($request->input('teamspeak-type')) {
            case 'public':
                return $this->postPublicRelation($group_id);
            case 'user':
                return $this->postUserRelation($group_id, $user_id);
            case 'role':
                return $this->postRoleRelation($group_id, $role_id);
            case 'corporation':
                return $this->postCorporationRelation($group_id, $corporation_id);
            case 'alliance':
                return $this->postAllianceRelation($group_id, $alliance_id);
            case 'title':
                return $this->postTitleRelation($group_id, $corporation_id,  $title_id);
            default:
                return redirect()->back()
                    ->with('error', 'Unknown relation type');
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

    public function getRemovePublic($teamspeak_sgid)
    {
        $public_filter = TeamspeakGroupPublic::where('teamspeak_sgid', $teamspeak_sgid);

        if ($public_filter != null) {
            $public_filter->delete();
            return redirect()->back()
                ->with('success', 'The public teamspeak relation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the public Teamspeak relation.');
    }

    public function getRemoveUser($user_id, $teamspeak_sgid)
    {
        $user_filter = TeamspeakGroupUser::where('group_id', $user_id)
            ->where('teamspeak_sgid', $teamspeak_sgid);

        if ($user_filter != null) {
            $user_filter->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the user has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the user.');
    }

    public function getRemoveRole($role_id, $teamspeak_sgid)
    {
        $role_filter = TeamspeakGroupRole::where('role_id', $role_id)
            ->where('teamspeak_sgid', $teamspeak_sgid);

        if ($role_filter != null) {
            $role_filter->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the role has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the role.');
    }

    public function getRemoveCorporation($corporation_id, $teamspeak_sgid)
    {
        $corporation_filter = TeamspeakGroupCorporation::where('corporation_id', $corporation_id)
            ->where('teamspeak_sgid', $teamspeak_sgid);

        if ($corporation_filter != null) {
            $corporation_filter->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the corporation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the corporation.');
    }

    public function getRemoveAlliance($alliance_id, $teamspeak_sgid)
    {
        $alliance_filter = TeamspeakGroupAlliance::where('alliance_id', $alliance_id)
            ->where('teamspeak_sgid', $teamspeak_sgid);

        if ($alliance_filter != null) {
            $alliance_filter->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the alliance has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the alliance.');
    }


    public function getRemoveTitle($corporation_id, $title_id, $teamspeak_sgid)
    {
        $title_filter = TeamspeakGroupTitle::where('corporation_id', $corporation_id)
            ->where('title_id', $title_id)
            ->where('teamspeak_sgid', $teamspeak_sgid);

        if ($title_filter != null) {
            $title_filter->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the title has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the title.');
    }

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

    private function postPublicRelation($teamspeak_sgid)
    {
        if (TeamspeakGroupPublic::find($teamspeak_sgid) == null) {
            TeamspeakGroupPublic::create([
                'teamspeak_sgid' => $teamspeak_sgid
            ]);

            return redirect()->back()
                ->with('success', 'New public teamspeak relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postUserRelation($teamspeak_sgid, $user_id)
    {
        $filter = TeamspeakGroupUser::where('teamspeak_sgid', '=', $teamspeak_sgid)
            ->where('group_id', '=', $user_id)
            ->get();

        if ($filter->count() == 0) {
            TeamspeakGroupUser::create([
                'group_id' => $user_id,
                'teamspeak_sgid' => $teamspeak_sgid]);

            return redirect()->back()
                ->with('success', 'New teamspeak user relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postRoleRelation($teamspeak_sgid, $role_id)
    {
        $filter = TeamspeakGroupRole::where('role_id', '=', $role_id)
            ->where('teamspeak_sgid', '=', $teamspeak_sgid)
            ->get();

        if ($filter->count() == 0) {
            TeamspeakGroupRole::create([
                'role_id' => $role_id,
                'teamspeak_sgid' => $teamspeak_sgid
            ]);

            return redirect()->back()
                ->with('success', 'New teamspeak role relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postTitleRelation($teamspeak_sgid, $corporation_id, $title_id)
    {
        $filter = TeamspeakGroupTitle::where('corporation_id', '=', $corporation_id)
            ->where('title_id', '=', $title_id)
            ->where('teamspeak_sgid', '=', $teamspeak_sgid)
            ->get();

        if ($filter->count() == 0) {
            TeamspeakGroupTitle::create([
                'corporation_id' => $corporation_id,
                'title_id' => $title_id,
                'teamspeak_sgid' => $teamspeak_sgid
            ]);

            return redirect()->back()
                ->with('success', 'New teamspeak title relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postCorporationRelation($teamspeak_sgid, $corporation_id)
    {
        $filter = TeamspeakGroupCorporation::where('corporation_id', '=', $corporation_id)
            ->where('teamspeak_sgid', '=', $teamspeak_sgid)
            ->get();

        if ($filter->count() == 0) {
            TeamspeakGroupCorporation::create([
                'corporation_id' => $corporation_id,
                'teamspeak_sgid' => $teamspeak_sgid
            ]);

            return redirect()->back()
                ->with('success', 'New teamspeak corporation relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    /**
     * @param $teamspeak_sgid
     * @param $alliance_id
     * @return \Illuminate\Http\RedirectResponse
     */
    private function postAllianceRelation($teamspeak_sgid, $alliance_id)
    {
        $filter = TeamspeakGroupAlliance::where('alliance_id', '=', $alliance_id)
            ->where('teamspeak_sgid', '=', $teamspeak_sgid)
            ->get();

        if ($filter->count() == 0) {
            TeamspeakGroupAlliance::create([
                'alliance_id' => $alliance_id,
                'teamspeak_sgid' => $teamspeak_sgid
            ]);

            return redirect()->back()
                ->with('success', 'New teamspeak alliance relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    /**
     * @return string
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
     */
    public function getUserID() {

        $this->getTeamspeak();

        $ts_tags = setting('teamspeak_tags', true);

        $main_character = auth()->user()->group->main_character->name;

        if ($ts_tags != '') {
            $character = auth()->user()->group->main_character;
            $corp = CorporationInfo::find($character->corporation_id);
            $main_character = sprintf('%s | %s', $corp->ticker, auth()->user()->group->main_character->name);
        }
        
        // Teamspeak has a 30 char limit on names. Trim it.
        $main_character = substr($main_character, 0, 30);

        $user_list = $this->teamspeak->clientList();
        foreach ($user_list as $user) {
            $nickname = preg_replace('/’/', '\'', $user->client_nickname->toString());
            if ($nickname === $main_character) {
                $uid = $user->client_unique_identifier->toString();
                $found_user = [];
                $found_user['id'] = $uid;
                $found_user['nick'] = $nickname;
                $this->postRegisterUser($uid);

                $teamspeak_user = TeamspeakUser::where('group_id', auth()->user()->group->id)->first();
                // search client information using client unique ID
                $user_info = $this->teamspeak->clientGetByUid($teamspeak_user->teamspeak_id, true);

                $allowed_groups = TeamspeakHelper::allowedGroups($teamspeak_user, true);
                $teamspeak_groups = $this->teamspeak->clientGetServerGroupsByDbid($user->client_database_id);
                $member_of_groups = [];
                foreach ($teamspeak_groups as $g) {
                    $member_of_groups[] = $g['sgid'];
                }

                $missing_groups = array_diff($allowed_groups, $member_of_groups);
                if (!empty($missing_groups)) {
                    $this->processGroupsInvitation($user_info, $missing_groups);
                    $this->logEvent($nickname, 'invite', $missing_groups);
                }
			    return json_encode($found_user);
            }
        }
        return json_encode([]);
    }

    /**
     * Invite an user to each group
     *
     * @param \TeamSpeak3_Node_Client $teamspeak_client_node
     * @param array $teamspeak_groups
     */
    private function processGroupsInvitation(TeamSpeak3_Node_Client $teamspeak_client_node, $teamspeak_groups)
    {
        // iterate over each group ID and add the user
        foreach ($teamspeak_groups as $teamspeak_sgid) {
            $this->teamspeak->serverGroupClientAdd($teamspeak_sgid, $teamspeak_client_node->client_database_id);
        }
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function getRegisterUser() {
        $main_character = auth()->user()->group->main_character;

        if (! $main_character) {
            redirect()->back()->with('error', 'Could not find your Main Character.  Check your Profile for the correct Main.');
        }

        $corp = CorporationInfo::find($main_character->corporation_id);

        if (! $corp) {
            redirect()->back()->with('error', 'Could not find your Corporation.  Please have your CEO upload a Corp API key to this website.');
        }
        
        $ts_tags = setting('teamspeak_tags', true);
        
        if ($ts_tags != '') {
            $main_character = sprintf('%s | %s', $corp->ticker, $main_character->name);
        }

        // Teamspeak has a 30 char limit on names. Trim it.
        $main_character = substr($main_character, 0, 30);

        return view('teamspeak::register', compact('main_character'));
    }

    private function postRegisterUser($uid)
    {
        $group_id = auth()->user()->group->id;
        
        $ts_user = TeamspeakUser::find($group_id);
        if (is_null($ts_user)) {
            TeamspeakUser::create([
                'group_id' => $group_id,
                'teamspeak_id' => $uid
            ]);
        }
        else {
            $ts_user->teamspeak_id = $uid;
            $ts_user->save();
        }
    }

    /**
     * Set the Teamspeak Server object
     *
     * @throws \Seat\Warlof\Teamspeak\Exceptions\TeamspeakSettingException
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function getTeamspeak()
    {
        // load token and team uri from settings
        $ts_username = setting('teamspeak_username', true);
        $ts_password = setting('teamspeak_password', true);
        $ts_hostname = setting('teamspeak_hostname', true);
        $ts_server_query = setting('teamspeak_server_query', true);
        $ts_server_voice = setting('teamspeak_server_port', true);

        if ($ts_username == null || $ts_password == null || $ts_hostname == null || $ts_server_query == null ||
            $ts_server_voice == null) {
            throw new TeamspeakSettingException("missing teamspeak_username, teamspeak_password, teamspeak_hostname, ".
                "teamspeak_server_query or teamspeak_server_port in settings");
        }

        $this->teamspeak = TeamspeakHelper::connect($ts_username, $ts_password, $ts_hostname, $ts_server_query, $ts_server_voice);
    }

    protected function logEvent($user, $event_type, $groups)
    {
        $message = '';

        switch ($event_type)
        {
            case 'invite':
                $message = 'The user ' . $user . ' has been invited to following groups : ' .
                    implode(',', $groups);
                break;
            case 'kick':
                $message = 'The user ' . $user . ' has been kicked from following groups : ' .
                    implode(',', $groups);
                break;
        }

        TeamspeakLog::create([
            'event' => $event_type,
            'message' => $message
        ]);
    }
}
