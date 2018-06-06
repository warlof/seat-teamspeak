<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\Acl\Role;

class TeamspeakGroupRole extends Model
{
    protected $fillable = ['role_id', 'tsgrp_id', 'enable'];

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'tsgrp_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}
