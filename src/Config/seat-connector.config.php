<?php
/**
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2019  Warlof Tutsimo <loic.leuilliot@gmail.com>
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

return [
    'name'     => 'teamspeak',
    'icon'     => 'fa-headphones',
    'client'   => \Warlof\Seat\Connector\Drivers\Teamspeak\Driver\TeamspeakClient::class,
    'settings' => [
        [
            'name'  => 'server_host',
            'label' => 'seat-connector-teamspeak::seat.server_host',
            'type'  => 'text',
        ],
        [
            'name'  => 'server_port',
            'label' => 'seat-connector-teamspeak::seat.server_port',
            'type'  => 'numeric|min:1|max:65535',
        ],
        [
            'name'  => 'query_port',
            'label' => 'seat-connector-teamspeak::seat.query_port',
            'type'  => 'numeric|min:1|max:65535',
        ],
        [
            'name'  => 'query_username',
            'label' => 'seat-connector-teamspeak::seat.query_username',
            'type'  => 'text',
        ],
        [
            'name'  => 'query_password',
            'label' => 'seat-connector-teamspeak::seat.query_password',
            'type'  => 'text',
        ],
    ],
];
