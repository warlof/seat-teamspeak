<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;

class TeamspeakGroupPublic extends Model
{
    protected $primaryKey = 'teamspeak_sgid';

    protected $fillable = ['teamspeak_sgid', 'enable'];

    protected $table = 'teamspeak_group_public';

    public function group()
    {
        return $this->belongsTo(TeamspeakGroup::class, 'teamspeak_sgid', 'id');
    }
}
