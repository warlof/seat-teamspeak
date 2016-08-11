<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 18:58
 */

namespace Seat\Warlof\Teamspeak\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Eve\AllianceList;
use Seat\Services\Settings\Seat;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroup;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupPublic;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupUser;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupRole;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupCorporation;
use Seat\Warlof\Teamspeak\Models\TeamspeakGroupAlliance;
use Seat\Warlof\Teamspeak\Models\TeamspeakLog;
use Seat\Warlof\Teamspeak\Validation\AddRelation;
use Seat\Warlof\Teamspeak\Validation\ValidateConfiguration;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;

class TeamspeakController extends Controller
{
    public function getRelations()
    {
        $groupPublic = TeamspeakGroupPublic::all();
        $groupUsers = TeamspeakGroupUser::all();
        $groupRoles = TeamspeakGroupRole::all();
        $groupCorporations = TeamspeakGroupCorporation::all();
        $groupAlliances = TeamspeakGroupAlliance::all();
        
        $users = User::all();
        $roles = Role::all();
        $corporations = CorporationSheet::all();
        $alliances = AllianceList::all();
        $groups = TeamspeakGroup::all();

        return view('teamspeak::list',
            compact('groupPublic', 'groupUsers', 'groupRoles', 'groupCorporations', 'groupAlliances',
                'users', 'roles', 'corporations', 'alliances', 'groups'));
    }

    public function getConfiguration()
    {
        $tsUsername = Seat::get('teamspeak_username');
        $tsPassword = Seat::get('teamspeak_password');
        $tsHostname = Seat::get('teamspeak_hostname');
        $tsServerQuery = Seat::get('teamspeak_server_query');
        $tsServerPort = Seat::get('teamspeak_server_port');
        $greenSettings = false;

        if ($tsUsername != "" && $tsPassword != "" && $tsHostname != "" && $tsServerQuery != "" && $tsServerPort != "") {
            $greenSettings = true;
        }

        $parser = new \Parsedown();
        $changelog = $parser->parse($this->getChangelog());
        
        return view('teamspeak::configuration', compact('tsUsername', 'tsPassword', 'tsHostname', 'tsServerQuery',
            'tsServerPort', 'changelog', 'greenSettings'));
    }
    
    public function getLogs()
    {
        $logs = TeamspeakLog::all();

        return view('teamspeak::logs', compact('logs'));
    }

    public function postRelation(AddRelation $request)
    {
        $userId = $request->input('teamspeak-user-id');
        $roleId = $request->input('teamspeak-role-id');
        $corporationId = $request->input('teamspeak-corporation-id');
        $allianceId = $request->input('teamspeak-alliance-id');
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
            default:
                return redirect()->back()
                    ->with('error', 'Unknown relation type');
        }
    }

    public function postConfiguration(ValidateConfiguration $request)
    {
        Seat::set('teamspeak_username', $request->input('teamspeak-configuration-username'));
        Seat::set('teamspeak_password', $request->input('teamspeak-configuration-password'));
        Seat::set('teamspeak_hostname', $request->input('teamspeak-configuration-hostname'));
        Seat::set('teamspeak_server_query', $request->input('teamspeak-configuration-query'));
        Seat::set('teamspeak_server_port', $request->input('teamspeak-configuration-port'));
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

}
