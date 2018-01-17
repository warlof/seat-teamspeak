<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Warlof\Teamspeak\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Seat\Web\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Corporation\Title;
use Seat\Eveapi\Models\Eve\AllianceList;
use Seat\Eveapi\Models\Character\CharacterSheet;
use Seat\Services\Settings\Seat;
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
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;
use Illuminate\Http\Request;

use TeamSpeak3;

class TeamspeakController extends Controller
{
    public function getRelations()
    {
        $groupPublic = TeamspeakGroupPublic::all();
        $groupUsers = TeamspeakGroupUser::all();
        $groupRoles = TeamspeakGroupRole::all();
        $groupCorporations = TeamspeakGroupCorporation::all();
        $groupAlliances = TeamspeakGroupAlliance::all();
        $groupTitles = TeamspeakGroupTitle::all();
        
        $users = User::all();
        $roles = Role::all();
        $corporations = CorporationSheet::all();
        $alliances = AllianceList::all();
        $groups = TeamspeakGroup::all();

        return view('teamspeak::list',
            compact('groupPublic', 'groupUsers', 'groupRoles', 'groupCorporations', 'groupAlliances', 'groupTitles',
                'users', 'roles', 'corporations', 'alliances', 'groups'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Seat\Services\Exceptions\SettingException
    **/
    public function getConfiguration()
    {
        $tsUsername = setting('teamspeak_username', true);
        $tsPassword = setting('teamspeak_password', true);
        $tsHostname = setting('teamspeak_hostname', true);
        $tsServerQuery = setting('teamspeak_server_query', true);
        $tsServerPort = setting('teamspeak_server_port', true);
        $tsTags = setting('teamspeak_tags', true);
        $greenSettings = false;

        if ($tsUsername != "" && $tsPassword != "" && $tsHostname != "" && $tsServerQuery != "" && $tsServerPort != "") {
            $greenSettings = true;
        }

        $parser = new \Parsedown();
        $changelog = $parser->parse($this->getChangelog());
        
        return view('teamspeak::configuration', compact('tsUsername', 'tsPassword', 'tsHostname', 'tsServerQuery',
            'tsServerPort', 'tsTags', 'changelog', 'greenSettings'));
    }
    
    public function getLogs()
    {
        $logs = TeamspeakLog::orderBy('created_at', 'desc')->take(30)->get();

        return view('teamspeak::logs', compact('logs'));
    }

    public function postRelation(AddRelation $request)
    {
        $userId = $request->input('teamspeak-user-id');
        $roleId = $request->input('teamspeak-role-id');
        $corporationId = $request->input('teamspeak-corporation-id');
        $allianceId = $request->input('teamspeak-alliance-id');
        $titleId = $request->input('teamspeak-title-id');
        $groupId = $request->input('teamspeak-group-id');

        // use a single post route in order to create any kind of relation
        // value are user, role, corporation or alliance
        switch ($request->input('teamspeak-type')) {
            case 'public':
                return $this->postPublicRelation($groupId);
            case 'user':
                return $this->postUserRelation($groupId, $userId);
            case 'role':
                return $this->postRoleRelation($groupId, $roleId);
            case 'corporation':
                return $this->postCorporationRelation($groupId, $corporationId);
            case 'alliance':
                return $this->postAllianceRelation($groupId, $allianceId);
            case 'title':
                return $this->postTitleRelation($groupId, $corporationId,  $titleId);
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

    public function getRemovePublic($groupId)
    {
        $groupPublic = TeamspeakGroupPublic::where('group_id', $groupId);

        if ($groupPublic != null) {
            $groupPublic->delete();
            return redirect()->back()
                ->with('success', 'The public teamspeak relation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the public Teamspeak relation.');
    }

    public function getRemoveUser($userId, $groupId)
    {
        $groupUser = TeamspeakGroupUser::where('user_id', $userId)
            ->where('group_id', $groupId);

        if ($groupUser != null) {
            $groupUser->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the user has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the user.');
    }

    public function getRemoveRole($roleId, $groupId)
    {
        $groupRole = TeamspeakGroupRole::where('role_id', $roleId)
            ->where('group_id', $groupId);

        if ($groupRole != null) {
            $groupRole->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the role has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the role.');
    }

    public function getRemoveCorporation($corporationId, $groupId)
    {
        $groupCorporation = TeamspeakGroupCorporation::where('corporation_id', $corporationId)
            ->where('group_id', $groupId);

        if ($groupCorporation != null) {
            $groupCorporation->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the corporation has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the corporation.');
    }

    public function getRemoveAlliance($allianceId, $groupId)
    {
        $groupAlliance = TeamspeakGroupAlliance::where('alliance_id', $allianceId)
            ->where('group_id', $groupId);

        if ($groupAlliance != null) {
            $groupAlliance->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the alliance has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the alliance.');
    }


    public function getRemoveTitle($corporationId, $titleId, $groupId)
    {
        $groupTitle = TeamspeakGroupTitle::where('corporation_id', $corporationId)
            ->where('title_id', $titleId)
            ->where('group_id', $groupId);

        if ($groupTitle != null) {
            $groupTitle->delete();
            return redirect()->back()
                ->with('success', 'The teamspeak relation for the title has been removed');
        }

        return redirect()->back()
            ->with('error', 'An error occurs while trying to remove the Teamspeak relation for the title.');
    }

    public function getSubmitJob($commandName)
    {
        $acceptedCommands = [
            'teamspeak:groups:update',
            'teamspeak:users:update',
            'teamspeak:logs:clear'
        ];
        
        if (!in_array($commandName, $acceptedCommands)) {
            abort(401);
        }

        Artisan::call($commandName);

        return redirect()->back()
            ->with('success', 'The command has been run.');
    }

    private function getChangelog()
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://raw.githubusercontent.com/warlof/seat-teamspeak/master/CHANGELOG.md");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        return curl_exec($curl);
    }

    private function postPublicRelation($groupId)
    {
        if (TeamspeakGroupPublic::find($groupId) == null) {
            TeamspeakGroupPublic::create([
                'group_id' => $groupId
            ]);

            return redirect()->back()
                ->with('success', 'New public teamspeak relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postUserRelation($groupId, $userId)
    {
        $relation = TeamspeakGroupUser::where('group_id', '=', $groupId)
            ->where('user_id', '=', $userId)
            ->get();

        if ($relation->count() == 0) {
            TeamspeakGroupUser::create([
                'user_id' => $userId,
                'group_id' => $groupId]);

            return redirect()->back()
                ->with('success', 'New teamspeak user relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postRoleRelation($groupId, $roleId)
    {
        $relation = TeamspeakGroupRole::where('role_id', '=', $roleId)
            ->where('group_id', '=', $groupId)
            ->get();

        if ($relation->count() == 0) {
            TeamspeakGroupRole::create([
                'role_id' => $roleId,
                'group_id' => $groupId
            ]);

            return redirect()->back()
                ->with('success', 'New teamspeak role relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postTitleRelation($groupId, $corporationId, $titleId)
    {
        $relation = TeamspeakGroupTitle::where('corporation_id', '=', $corporationId)
            ->where('title_id', '=', $titleId)
            ->where('group_id', '=', $groupId)
            ->get();

        if ($relation->count() == 0) {
            TeamspeakGroupTitle::create([
                'corporation_id' => $corporationId,
                'title_id' => $titleId,
                'group_id' => $groupId
            ]);

            return redirect()->back()
                ->with('success', 'New teamspeak title relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postCorporationRelation($groupId, $corporationId)
    {
        $relation = TeamspeakGroupCorporation::where('corporation_id', '=', $corporationId)
            ->where('group_id', '=', $groupId)
            ->get();

        if ($relation->count() == 0) {
            TeamspeakGroupCorporation::create([
                'corporation_id' => $corporationId,
                'group_id' => $groupId
            ]);

            return redirect()->back()
                ->with('success', 'New teamspeak corporation relation has been created');
        }

        return redirect()->back()
            ->with('error', 'This relation already exists');
    }

    private function postAllianceRelation($groupId, $allianceId)
    {
        $relation = TeamspeakGroupAlliance::where('alliance_id', '=', $allianceId)
            ->where('group_id', '=', $groupId)
            ->get();

        if ($relation->count() == 0) {
            TeamspeakGroupAlliance::create([
                'alliance_id' => $allianceId,
                'group_id' => $groupId
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
    */
    public function getUserID() {

        $tsUsername = setting('teamspeak_username', true);
        $tsPassword = setting('teamspeak_password', true);
        $tsHostname = setting('teamspeak_hostname', true);
        $tsServerQuery = setting('teamspeak_server_query', true);
        $tsServerPort = setting('teamspeak_server_port', true);
        $tsTags = setting('teamspeak_tags', true);

        if ($tsTags != '') {
            $character = CharacterSheet::find(setting('main_character_id'));
            $corp = CorporationSheet::find($character->corporationID);
            $main_character = "[" . $corp->ticker . "] ".setting('main_character_name');
        } else {
            $main_character = setting('main_character_name');
        }

        $serverQuery = sprintf("serverquery://%s:%s@%s:%s/?server_port=%s&blocking=0", $tsUsername, $tsPassword,
            $tsHostname, $tsServerQuery, $tsServerPort);
        $ts3Server = TeamSpeak3::factory($serverQuery);
        
        $userList = $ts3Server->clientList();
        foreach ($userList as $user) {
            $nickname = preg_replace('/’/', '\'', $user->client_nickname->toString());
            if ($nickname === $main_character) {
                    $uid = $user->client_unique_identifier->toString();
                    $founduser = [];
                    $founduser['id'] = $uid;
                    $founduser['nick'] = $nickname;
                    $this->postRegisterUser($uid);
			return json_encode($founduser);
            }
        }
        return json_encode([]);
    }

    public function getRegisterUser() {
        $character = CharacterSheet::find(setting('main_character_id'));
        $corp = CorporationSheet::find($character->corporationID);
        $ticker = $corp->ticker;
        $tags = setting('teamspeak_tags', true);

        return view('teamspeak::register', compact('ticker', 'tags'));
    }

    private function postRegisterUser($uid)
    {
        $userId = auth()->user()->id;
        
        $tsUser = TeamspeakUser::find($userId); 
        if ($tsUser == null) {
            TeamspeakUser::create([
                'user_id' => $userId,
                'teamspeak_id' => $uid
            ]);
        }
        else {
            $tsUser->teamspeak_id = $uid;
            $tsUser->save();
        }
        return;
    }
}
