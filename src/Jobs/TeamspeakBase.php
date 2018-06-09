<?php

namespace Seat\Warlof\Teamspeak\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class TeamspeakJobBase
 * @package Seat\Warlof\Teamspeak\Jobs
 */
abstract class TeamspeakBase implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array
     */
    protected $tags = [];

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

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

}
