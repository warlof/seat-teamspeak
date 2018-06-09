<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;

class TeamspeakGroupPublic extends Model
{
    protected $primaryKey = 'tsgrp_id';

    protected $fillable = ['tsgrp_id', 'enable'];

    protected $table = 'teamspeak_group_public';

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'tsgrp_id', 'id');
    }
}
