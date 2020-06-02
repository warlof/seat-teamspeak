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

Route::group([
    'namespace'  => 'Warlof\Seat\Connector\Drivers\Teamspeak\Http\Controllers',
    'prefix'     => 'seat-connector',
    'middleware' => ['web', 'auth', 'locale'],
], function () {

    Route::group([
        'prefix' => 'registration',
    ], function () {

        Route::get('/teamspeak', [
            'as'   => 'seat-connector.drivers.teamspeak.registration',
            'uses' => 'RegistrationController@redirectToProvider',
        ]);

        Route::post('/teamspeak', [
            'as'   => 'seat-connector.drivers.teamspeak.registration.callback',
            'uses' => 'RegistrationController@handleProviderCallback',
        ]);

    });

    Route::group([
        'prefix' => 'settings',
        'middleware' => 'can:global.superuser',
    ], function () {

        Route::post('/teamspeak', [
            'as' => 'seat-connector.drivers.teamspeak.settings',
            'uses' => 'SettingsController@store',
        ]);

    });

});
