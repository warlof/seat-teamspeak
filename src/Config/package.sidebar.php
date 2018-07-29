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

return [
    'teamspeak' => [
        'name'          => 'Teamspeak Connector',
        'icon'          => 'fa-microphone',
        'route_segment' => 'teamspeak',
        'entries' => [
            [
                'name'          => 'Join Server',
                'icon'          => 'fa-sign-in',
                'route_segment' => 'teamspeak',
                'route'         => 'teamspeak.register',
                'permission'    => 'teamspeak.view',
            ],
            [
                'name'       => 'Access Management',
                'icon'       => 'fa-shield',
                'route'      => 'teamspeak.list',
                'permission' => 'teamspeak.setup',
            ],
            [
                'name'       => 'Settings',
                'icon'       => 'fa-cogs',
                'route'      => 'teamspeak.configuration',
                'permission' => 'teamspeak:setup',
            ],
            [
                'name'       => 'Logs',
                'icon'       => 'fa-list',
                'route'      => 'teamspeak.logs',
                'permission' => 'teamspeak.setup',
            ]
        ],
    ]
];
