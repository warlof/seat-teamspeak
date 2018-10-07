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

namespace Warlof\Seat\Connector\Teamspeak\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TeamSpeak3_Node_Server;

/**
 * Class TeamspeakJobBase
 * @package Warlof\Seat\Connector\Teamspeak\Jobs
 */
abstract class TeamspeakJobBase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;
    /**
     * @var \TeamSpeak3_Node_Server
     */
    protected $client;
    /**
     * @var array
     */
    protected $tags = [];

    /**
     * Assign this job a tag so that Horizon can categorize and allow
     * for specific tags to be monitored.
     *
     * If a job specifies the tags property, that is added.
     *
     * @return array
     */
    public function tags(): array
    {
        $tags = ['teamspeak'];

        if (property_exists($this, 'tags'))
            return array_merge($this->tags, $tags);

        return $tags;
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public abstract function handle();

    /**
     * @return \TeamSpeak3_Node_Server
     * @throws \Warlof\Seat\Connector\Teamspeak\Exceptions\TeamspeakSettingException
     */
    protected abstract function teamspeak(): TeamSpeak3_Node_Server;
}
