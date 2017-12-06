<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 11/08/2016
 * Time: 15:59
 */

namespace Seat\Warlof\Teamspeak\Helpers;

use TeamSpeak3;

class TeamspeakHelper
{
    /**
     * @param $username
     * @param $password
     * @param $hostname
     * @param $server_query_port
     * @param $instance_port
     *
     * @return \TeamSpeak3_Adapter_Abstract
     */
    public static function connect( $username, $password, $hostname, $server_query_port, $instance_port)
    {
        $serverQuery = sprintf("serverquery://%s:%s@%s:%s/?server_port=%s&blocking=0", $username, $password,
            $hostname, $server_query_port, $instance_port);

        return TeamSpeak3::factory($serverQuery);
    }
}
