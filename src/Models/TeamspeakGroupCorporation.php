<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationInfo;

class TeamspeakGroupCorporation extends Model
{
    protected $fillable = ['corporation_id', 'teamspeak_sgid', 'enable'];

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'teamspeak_sgid', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }
}
