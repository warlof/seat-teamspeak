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

use Seat\Eveapi\Models\Alliances\Alliance;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Corporation\CorporationTitle;
use Seat\Web\Models\Acl\Role;
use Seat\Web\Models\User;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroup;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupAlliance;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupCorporation;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupPublic;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupRole;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupTitle;
use Warlof\Seat\Connector\Teamspeak\Models\TeamspeakGroupUser;
use Warlof\Seat\Connector\Teamspeak\Validation\AddRelation;

class AccessManagementController
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
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

        return view('teamspeak::access.list',
            compact('group_public', 'group_users', 'group_roles', 'group_corporations', 'group_alliances', 'group_titles',
                'users', 'roles', 'corporations', 'alliances', 'groups'));
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTitles()
    {
        $corporation_id = request()->input('corporation_id');

        if (!empty($corporation_id)) {
            $titles = CorporationTitle::where('corporation_id', $corporation_id)->select('title_id', 'name')
                ->get();

            return response()->json($titles->map(
                function ($item) {
                    return [
                        'id' => $item->title_id,
                        'name' => strip_tags($item->name)
                    ];
                })
            );
        }

        return response()->json([]);
    }

    /**
     * @param $teamspeak_sgid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removePublic($teamspeak_sgid)
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

    /**
     * @param $user_id
     * @param $teamspeak_sgid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeUser($user_id, $teamspeak_sgid)
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

    /**
     * @param $role_id
     * @param $teamspeak_sgid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeRole($role_id, $teamspeak_sgid)
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

    /**
     * @param $corporation_id
     * @param $teamspeak_sgid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeCorporation($corporation_id, $teamspeak_sgid)
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

    /**
     * @param $alliance_id
     * @param $teamspeak_sgid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeAlliance($alliance_id, $teamspeak_sgid)
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

    /**
     * @param $corporation_id
     * @param $title_id
     * @param $teamspeak_sgid
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removeTitle($corporation_id, $title_id, $teamspeak_sgid)
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

    /**
     * @param AddRelation $request
     * @return \Illuminate\Http\RedirectResponse
     */
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
                return $this->postTitleRelation($group_id, $corporation_id, $title_id);
            default:
                return redirect()->back()
                    ->with('error', 'Unknown relation type');
        }
    }

    /**
     * @param $teamspeak_sgid
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * @param $teamspeak_sgid
     * @param $user_id
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * @param $teamspeak_sgid
     * @param $role_id
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * @param $teamspeak_sgid
     * @param $corporation_id
     * @return \Illuminate\Http\RedirectResponse
     */
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
     * @param $teamspeak_sgid
     * @param $corporation_id
     * @param $title_id
     * @return \Illuminate\Http\RedirectResponse
     */
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
}
