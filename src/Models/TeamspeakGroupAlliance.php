<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Eve\AllianceList;

class TeamspeakGroupAlliance extends Model
{
    protected $fillable = ['alliance_id', 'group_id', 'enable'];

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'group_id', 'id');
    }

    public function alliance()
    {
        return $this->belongsTo(AllianceList::class, 'alliance_id', 'allianceID');
    }
}
