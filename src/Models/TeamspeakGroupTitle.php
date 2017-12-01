<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Eveapi\Models\Corporation\CorporationSheet;
use Seat\Eveapi\Models\Corporation\Title;

class TeamspeakGroupTitle extends Model
{
    protected $fillable = ['title_id', 'group_id', 'title_surrogate_key', 'corporation_id', 'enable'];

    public static function create(array $attributes = [])
    {
        // search for primary key assigned to the surrogate key
        $title = Title::where('corporationID', $attributes['corporation_id'])
            ->where('titleID', $attributes['title_id'])
            ->first();

        $attributes['title_surrogate_key'] = $title->id;

        parent::create($attributes);
    }

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'group_id', 'id');
    }

    public function corporation()
    {
        return $this->belongsTo(CorporationSheet::class, 'corporation_id', 'corporationID');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'title_surrogate_key', 'id');
    }
}
