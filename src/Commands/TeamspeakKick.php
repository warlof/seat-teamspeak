<?php

/**
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2018  Warlof Tutsimo <loic.leuilliot@gmail.com>
 *
 * SeAT Teamspeak Connector  is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * SeAT Teamspeak Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Connector\Teamspeak\Commands;

use Illuminate\Console\Command;
use Warlof\Seat\Connector\Teamspeak\Jobs\TeamspeakAssKicker;

class TeamspeakKick extends Command
{
    /**
     * @var string
     */
    protected $signature = 'teamspeak:users:kick';

    /**
     * @var string
     */
    protected $description = 'Auto kick based on white list/teamspeak relation';

    /**
     *
     */
    public function handle()
    {
        TeamspeakAssKicker::dispatch();
    }
}
