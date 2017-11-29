<?php
/**
 * User: Warlof Tutsimo <loic.leuilliot@gmail.com>
 * Date: 15/06/2016
 * Time: 20:01
 */

return [
    'ts3register' => [
        'name'          => 'Teamspeak Register',
        'icon'          => 'fa-microphone',
        'route_segment' => 'teamspeak',
        'route'         => 'ts3.register',
        'permission'    => 'teamspeak.view'
    ],
    'teamspeak' => [
        'name'          => 'Teamspeak',
        'icon'          => 'fa-microphone',
        'route_segment' => 'teamspeak',
        'entries' => [
            [
                'name'  => 'Access Management',
                'icon'  => 'fa-shield',
                'route' => 'teamspeak.list',
            ],
            [
                'name'  => 'Settings',
                'icon'  => 'fa-cogs',
                'route' => 'teamspeak.configuration',
                'permission' => 'teamspeak:setup'
            ],
            [
                'name'  => 'Logs',
                'icon'  => 'fa-list',
                'route' => 'teamspeak.logs'
            ]
        ],
        'permission' => 'teamspeak.setup'
    ]
];
