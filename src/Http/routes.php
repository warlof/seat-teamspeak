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
    'namespace' => 'Warlof\Seat\Connector\Teamspeak\Http\Controllers',
    'prefix' => 'teamspeak'
], function() {

    Route::group([
        'middleware' => ['web', 'auth'],
    ], function () {

        Route::get('/register', [
            'as' => 'teamspeak.register',
            'uses' => 'TeamspeakController@getRegisterUser',
            'middleware' => 'bouncer:teamspeak.view',
        ]);

        Route::group([
            'prefix' => 'api',
        ], function() {
            Route::post('/user', [
                'as'   => 'teamspeak.api.user',
                'uses' => 'TeamspeakController@postGetUserUid',
                'middleware' => 'bouncer:teamspeak.view',
            ]);

            Route::group([
                'prefix' => 'acl',
            ], function() {
                Route::get('/titles', [
                    'as' => 'teamspeak.api.acl.titles',
                    'uses' => 'AccessManagementController@getTitles',
                    'middleware' => 'bouncer:teamspeak.setup'
                ]);
            });
        });

        Route::group([
            'prefix' => 'acl',
            'middleware' => 'bouncer:teamspeak.setup',
        ], function() {
            Route::get('/', [
                'as' => 'teamspeak.list',
                'uses' => 'AccessManagementController@getRelations'
            ]);

            Route::post('/', [
                'as' => 'teamspeak.add',
                'uses' => 'AccessManagementController@postRelation',
            ]);

            Route::get('/public/{group_id}/remove', [
                'as' => 'teamspeak.public.remove',
                'uses' => 'AccessManagementController@getRemovePublic',
            ]);

            Route::get('/users/{user_id}/{group_id}/remove', [
                'as' => 'teamspeak.user.remove',
                'uses' => 'AccessManagementController@getRemoveUser',
            ]);

            Route::get('/roles/{role_id}/{group_id}/remove', [
                'as' => 'teamspeak.role.remove',
                'uses' => 'AccessManagementController@getRemoveRole',
            ]);

            Route::get('/corporations/{corporation_id}/{group_id}/remove', [
                'as' => 'teamspeak.corporation.remove',
                'uses' => 'AccessManagementController@getRemoveCorporation',
            ]);

            Route::get('/alliances/{alliance_id}/{group_id}/remove', [
                'as' => 'teamspeak.alliance.remove',
                'uses' => 'AccessManagementController@getRemoveAlliance',
            ]);

            Route::get('/titles/{corporation_id}/{title_id}/{group_id}/remove', [
                'as' => 'teamspeak.title.remove',
                'uses' => 'AccessManagementController@getRemoveTitle',
            ]);
        });

        Route::group([
            'prefix' => 'configuration',
            'middleware' => 'bouncer:teamspeak.setup',
        ], function() {
            Route::get('/', [
                'as' => 'teamspeak.configuration',
                'uses' => 'SettingsController@getConfiguration',
            ]);

            Route::post('/', [
                'as' => 'teamspeak.configuration.post',
                'uses' => 'SettingsController@postConfiguration',
            ]);

            Route::post('/run', [
                'as' => 'teamspeak.command.run',
                'uses' => 'SettingsController@postSubmitJob',
            ]);
        });

        Route::group([
            'prefix' => 'logs',
            'middleware' => 'bouncer:teamspeak.setup',
        ], function() {
            Route::get('/', [
                'as' => 'teamspeak.logs',
                'uses' => 'LogController@getLogs',
            ]);
        });

    });
});
