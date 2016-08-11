<?php

/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 09/08/2016
 * Time: 16:25
 */

namespace Seat\Teamspeak\Tests;

use Orchestra\Testbench\TestCase;
use Seat\Services\Settings\Seat;
use Seat\Teamspeak\Commands\TeamspeakGroupsUpdate;
use Seat\Teamspeak\Helpers\SlackApi;
use Seat\Teamspeak\Models\TeamspeakGroup;

class TeamspeakUpdateGroupsTest extends TestCase
{
    /**
     * @var SlackApi
     */
    private $teamspeak;

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => getenv('database_host'),
            'database' => getenv('database_name'),
            'username' => getenv('database_user'),
            'password' => getenv('database_pass'),
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => ''
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        // setup Slack API
        $tsUsername = getenv('teamspeak_username');
        $tsPassword = getenv('teamspeak_password');
        $tsHostname = getenv('teamspeak_hostname');
        $tsServerQuery = getenv('teamspeak_server_query');
        $tsServerPort = getenv('teamspeak_server_port');

        $this->teamspeak = $teamspeakServer = \TeamSpeak3::factory("serverquery://" . $tsUsername . ':' . $tsPassword .
            '@' . $tsHostname . ':' . $tsServerQuery . "/?server_port=" . $tsServerPort);
    }

    public function testChannelUpdate()
    {
        Seat::set('teamspeak_username',getenv('teamspeak_username'));
        Seat::set('teamspeak_password',getenv('teamspeak_password'));
        Seat::set('teamspeak_hostname',getenv('teamspeak_hostname'));
        Seat::set('teamspeak_server_query',getenv('teamspeak_server_query'));
        Seat::set('teamspeak_server_port',getenv('teamspeak_server_port'));

        // get list of channels
        $groups = $this->teamspeak->serverGroupList();

        // store all channels in an array of object
        $artifacts = [];

        foreach ($groups as $g) {
            $artifacts[] = new TeamspeakGroup([
                'id' => $g->sgid,
                'name' => $g->name
            ]);
        }

        // call slack:update:channels command
        $job = new TeamspeakGroupsUpdate();
        $job->handle();

        // fetch in database channels
        $inDatabase = TeamspeakGroup::all(['id', 'name']);

        // convert to an array of "new object"
        $result = [];

        foreach ($inDatabase as $object) {
            $result[] = new TeamspeakGroup([
                'id' => $object->id,
                'name' => $object->name
            ]);
        }

        // compare both array
        $this->assertEquals($artifacts, $result);
    }
}
