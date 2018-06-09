<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Alliances\Alliance;

class TeamspeakGroupAlliance extends Model
{
    protected $fillable = ['alliance_id', 'teamspeak_sgid', 'enable'];

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'teamspeak_sgid', 'id');
    }

    public function alliance()
    {
        return $this->belongsTo(Alliance::class, 'alliance_id', 'alliance_id');
    }
}
