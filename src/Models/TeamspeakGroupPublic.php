<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;

class TeamspeakGroupPublic extends Model
{
    protected $primaryKey = 'group_id';

    protected $fillable = ['group_id', 'enable'];

    protected $table = 'teamspeak_group_public';

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'group_id', 'id');
    }
}
