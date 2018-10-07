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

namespace Warlof\Seat\Connector\Teamspeak\Database\Seeds;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    protected $schedule = [
        [
            'command' => 'teamspeak:update',
            'expression' => '*/5 * * * * *',
            'allow_overlap' => false,
            'allow_maintenance' => false,
            'ping_before' => null,
            'ping_after' => null
        ]
    ];

    public function run()
    {
        foreach ($this->schedule as $job) {
            $existing = DB::table('schedules')
                ->where('command', $job['command'])
                ->where('expression', $job['expression'])
                ->first();

            if (!$existing) {
                DB::table('schedules')->insert($job);
            }
        }
    }
}
