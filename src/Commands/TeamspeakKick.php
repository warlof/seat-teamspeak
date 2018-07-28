<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Warlof\Teamspeak\Commands;


use Illuminate\Console\Command;
use Seat\Warlof\Teamspeak\Jobs\TeamspeakAssKicker;

class TeamspeakKick extends Command
{
    protected $signature = 'teamspeak:users:kick';

    protected $description = 'Auto kick based on white list/teamspeak relation';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        TeamspeakAssKicker::dispatch();
    }
}
