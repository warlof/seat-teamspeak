<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\Acl\Role;

class TeamspeakGroupRole extends Model
{
    protected $fillable = ['role_id', 'teamspeak_sgid', 'enable'];

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'teamspeak_sgid', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
