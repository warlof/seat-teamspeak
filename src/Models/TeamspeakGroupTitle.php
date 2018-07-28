<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Seat\Eveapi\Models\Corporation\CorporationTitle;

class TeamspeakGroupTitle extends Model
{
    protected $fillable = ['title_id', 'teamspeak_sgid', 'title_surrogate_key', 'corporation_id', 'enable'];

    public static function create(array $attributes = [])
    {
        // search for primary key assigned to the surrogate key
        $title = CorporationTitle::where('corporation_id', $attributes['corporation_id'])
            ->where('titleID', $attributes['title_id'])
            ->first();

        $attributes['title_surrogate_key'] = $title->id;

        parent::create($attributes);
    }

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'teamspeak_sgid', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(CorporationInfo::class, 'corporation_id', 'corporation_id');
    }

    public function title()
    {
        return $this->belongsTo(CorporationTitle::class, 'title_surrogate_key', 'id');
    }
}
