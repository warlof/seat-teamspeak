<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 17/06/2016
 * Time: 18:51
 */

namespace Seat\Warlof\Teamspeak\Commands;


use Illuminate\Console\Command;
use Seat\Warlof\Teamspeak\Models\TeamspeakLog;

class TeamspeakLogsClear extends Command
{
    protected $signature = 'teamspeak:logs:clear';

    protected $description = 'Clearing slack logs';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        TeamspeakLog::truncate();
    }
}
