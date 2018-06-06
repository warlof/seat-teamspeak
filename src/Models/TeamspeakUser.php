<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\Group;

class TeamspeakUser extends Model
{
    protected $fillable = [
        'group_id', 'teamspeak_id'
    ];

    protected $primaryKey = 'group_id';
    
    public function user()
    {
        return $this->belongsTo(Group::class, 'group_id', 'id');
    }
}
