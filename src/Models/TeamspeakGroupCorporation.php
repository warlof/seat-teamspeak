<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationSheet;

class TeamspeakGroupCorporation extends Model
{
    protected $fillable = ['corporation_id', 'group_id', 'enable'];

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'group_id', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(CorporationSheet::class, 'corporation_id', 'corporationID');
    }
}
