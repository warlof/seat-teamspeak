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
], function(){

    Route::group([
        'middleware' => 'web'
    ], function () {

        Route::get('/', [
            'as' => 'teamspeak.list',
            'uses' => 'TeamspeakController@getRelations'
        ]);

        Route::get('/getuserid', [
            'as' => 'teamspeak.getclients',
            'uses' => 'TeamspeakController@getUserID'
        ]);

        Route::get('/ts3register', [
            'as' => 'ts3.register',
            'uses' => 'TeamspeakController@getRegisterUser'
        ]);

        Route::get('/public/{group_id}/remove', [
            'as' => 'teamspeak.public.remove',
            'uses' => 'TeamspeakController@getRemovePublic',
            'middleware' => 'bouncer:teamspeak.create'
        ]);

        Route::get('/users/{user_id}/{group_id}/remove', [
            'as' => 'teamspeak.user.remove',
            'uses' => 'TeamspeakController@getRemoveUser',
            'middleware' => 'bouncer:teamspeak.create'
        ]);

        Route::get('/roles/{role_id}/{group_id}/remove', [
            'as' => 'teamspeak.role.remove',
            'uses' => 'TeamspeakController@getRemoveRole',
            'middleware' => 'bouncer:teamspeak.create'
        ]);

        Route::get('/corporations/{corporation_id}/{group_id}/remove', [
            'as' => 'teamspeak.corporation.remove',
            'uses' => 'TeamspeakController@getRemoveCorporation',
            'middleware' => 'bouncer:teamspeak.create'
        ]);

        Route::get('/alliances/{alliance_id}/{group_id}/remove', [
            'as' => 'teamspeak.alliance.remove',
            'uses' => 'TeamspeakController@getRemoveAlliance',
            'middleware' => 'bouncer:teamspeak.create'
        ]);

        Route::get('/titles/{corporation_id}/{title_id}/{group_id}/remove', [
            'as' => 'teamspeak.title.remove',
            'uses' => 'TeamspeakController@getRemoveTitle',
            'middleware' => 'bouncer:teamspeak.create'
        ]);

        Route::post('/', [
            'as' => 'teamspeak.add',
            'uses' => 'TeamspeakController@postRelation',
            'middleware' => 'bouncer:teamspeak.create'
        ]);

        Route::get('/configuration', [
            'as' => 'teamspeak.configuration',
            'uses' => 'TeamspeakController@getConfiguration',
            'middleware' => 'bouncer:teamspeak.setup'
        ]);
    
        Route::get('/logs', [
            'as' => 'teamspeak.logs',
            'uses' => 'TeamspeakController@getLogs',
            'middleware' => 'bouncer:teamspeak.setup'
        ]);

        Route::get('/run/{commandName}', [
            'as' => 'teamspeak.command.run',
            'uses' => 'TeamspeakController@getSubmitJob',
            'middleware' => 'bouncer:teamspeak.setup'
        ]);

        Route::post('/configuration', [
            'as' => 'teamspeak.configuration.post',
            'uses' => 'TeamspeakController@postConfiguration',
            'middleware' => 'bouncer:teamspeak.setup'
        ]);

    Route::group([
         'prefix' => 'json'
    ], function(){

        Route::get('/titles', [
            'as' => 'teamspeak.json.titles',
            'uses' => 'TeamspeakJsonController@getJsonTitle',
            'middleware' => 'bouncer:teamspeak.create'
        ]);
    });

});
});
