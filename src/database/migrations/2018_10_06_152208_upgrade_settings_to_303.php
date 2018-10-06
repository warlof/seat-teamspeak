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

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpgradeSettingsTo303 extends Migration
{
    const SETTINGS_MAP = [
        'teamspeak_hostname'     => 'warlof.teamspeak-connector.server.hostname',
        'teamspeak_server_port'  => 'warlof.teamspeak-connector.server.instance.port',
        'teamspeak_password'     => 'warlof.teamspeak-connector.server.query.password',
        'teamspeak_username'     => 'warlof.teamspeak-connector.server.query.username',
        'teamspeak_server_query' => 'warlof.teamspeak-connector.server.query.port',
        'teamspeak_tags'         => 'warlof.teamspeak-connector.tags',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function up()
    {
        foreach (self::SETTINGS_MAP as $old_key => $new_key) {
            // retrieve current setting value
            $value = setting($old_key, true);

            // if it's set, create new setting entry
            if (! is_null($value))
                setting([$new_key, $value], true);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws \Seat\Services\Exceptions\SettingException
     */
    public function down()
    {
        foreach (self::SETTINGS_MAP as $old_key => $new_key) {
            // retrieve current setting value
            $value = setting($new_key, true);

            // if it's set, create new setting entry
            if (! is_null($value))
                setting([$old_key, $value], true);
        }
    }
}
