<?php

namespace Seat\Warlof\Teamspeak\Models;

use Illuminate\Database\Eloquent\Model;
use Seat\Web\Models\User;

class TeamspeakUser extends Model
{
    protected $fillable = [
        'user_id', 'teamspeak_id'
    ];

    protected $primaryKey = 'user_id';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
