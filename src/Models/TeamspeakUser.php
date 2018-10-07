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

namespace Warlof\Seat\Connector\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\Group;

/**
 * Class TeamspeakUser
 * @package Warlof\Seat\Connector\Teamspeak\Models
 */
class TeamspeakUser extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'group_id', 'teamspeak_id'
    ];

    /**
     * @var string
     */
    protected $primaryKey = 'group_id';

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }

    /**
     * @return bool
     */
    public function isGranted(): bool
    {
        return $this->group->refresh_tokens->count() === $this->group->refresh_tokens()->withTrashed()->count();
    }

    /**
     * Return an array of server group ID to which the current user is granted
     *
     * @return array An array of unique server group ID
     */
    public function allowedGroups(): array
    {
        $rows = $this->getServerGroupsUserBased($this, false)
            ->union($this->getServerGroupsRoleBased($this, false))
            ->union($this->getServerGroupsCorporationBased($this, false))
            ->union($this->getServerGroupsAllianceBased($this, false))
            ->union($this->getServerGroupsPublicBased(false))
            ->get();

        return $rows->unique('teamspeak_sgid')->pluck('teamspeak_sgid')->toArray();
    }

    /**
     * Return all servers groups ID related to user mapping matching to the TeamspeakUser.
     *
     * @param TeamspeakUser $user The user to which mapping have to be applied
     * @param bool $get If false, the return is a QueryBuilder; otherwise it's a Collection
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection
     */
    public function getServerGroupsUserBased(TeamspeakUser $user, bool $get = true)
    {
        $roles = TeamspeakGroupUser::join('groups', 'teamspeak_group_users.group_id', '=', 'groups.id')
            ->join('teamspeak_groups', 'teamspeak_group_users.teamspeak_sgid', '=', 'teamspeak_groups.id')
            ->where('groups.id', $user->group_id)
            ->where('teamspeak_groups.is_server_group', true)
            ->select('teamspeak_sgid');

        if ($get)
            return $roles->get();

        return $roles;
    }

    /**
     * Return all servers groups ID related to roles mapping matching to the TeamspeakUser.
     *
     * @param TeamspeakUser $user The user to which mapping have to be applied
     * @param bool $get If false, the return is a QueryBuilder; otherwise it's a Collection
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection
     */
    public function getServerGroupsRoleBased(TeamspeakUser $user, bool $get = true)
    {
        $roles = TeamspeakGroupRole::join('group_role', 'teamspeak_group_roles.role_id', '=', 'group_role.role_id')
            ->join('teamspeak_groups', 'teamspeak_group_roles.teamspeak_sgid', '=', 'teamspeak_groups.id')
            ->where('group_role.group_id', $user->group_id)
            ->where('teamspeak_groups.is_server_group', true)
            ->select('teamspeak_sgid');

        if ($get)
            return $roles->get();

        return $roles;
    }

    /**
     * Return all servers groups ID related to corporation mapping matching to the TeamspeakUser.
     *
     * @param TeamspeakUser $user The user to which mapping have to be applied
     * @param bool $get If false, the return is a QueryBuilder; otherwise it's a Collection
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection
     */
    public function getServerGroupsCorporationBased(TeamspeakUser $user, bool $get = true)
    {
        $roles = TeamspeakGroupCorporation::join('character_infos', 'teamspeak_group_corporations.corporation_id', '=', 'character_infos.corporation_id')
            ->join('teamspeak_groups', 'teamspeak_group_corporations.teamspeak_sgid', '=', 'teamspeak_groups.id')
            ->whereIn('character_infos.character_id', $user->group->users->pluck('id')->toArray())
            ->where('teamspeak_groups.is_server_group', true)
            ->select('teamspeak_sgid');

        if ($get)
            return $roles->get();

        return $roles;
    }

    /**
     * Return all servers groups ID related to alliance mapping matching to the TeamspeakUser.
     *
     * @param TeamspeakUser $user The user to which mapping have to be applied
     * @param bool $get If false, the return is a QueryBuilder; otherwise it's a Collection
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection
     */
    public function getServerGroupsAllianceBased(TeamspeakUser $user, bool $get = true)
    {
        $roles = TeamspeakGroupAlliance::join('character_infos', 'teamspeak_group_alliances.alliance_id', '=', 'character_infos.alliance_id')
            ->join('teamspeak_groups', 'teamspeak_group_alliances.teamspeak_sgid', '=', 'teamspeak_groups.id')
            ->whereIn('character_infos.character_id', $user->group->users->pluck('id')->toArray())
            ->where('teamspeak_groups.is_server_group', true)
            ->select('teamspeak_sgid');

        if ($get)
            return $roles->get();

        return $roles;
    }

    /**
     * Return all servers groups ID related to public mapping matching to the current TeamspeakUser.
     *
     * @param bool $get If false, the return is a QueryBuilder; otherwise it's a Collection
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Collection
     */
    public function getServerGroupsPublicBased(bool $get = true)
    {
        $roles = TeamspeakGroupPublic::join('teamspeak_groups', 'teamspeak_group_public.teamspeak_sgid', '=', 'teamspeak_groups.id')
            ->where('teamspeak_groups.is_server_group', true)
            ->select('teamspeak_sgid');

        if ($get)
            return $roles->get();

        return $roles;
    }
}
