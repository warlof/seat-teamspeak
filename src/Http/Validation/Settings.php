<?php
/*
 * This file is part of SeAT Teamspeak Connector.
 *
 * Copyright (C) 2020  Warlof Tutsimo <loic.leuilliot@gmail.com>
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

namespace Warlof\Seat\Connector\Drivers\Teamspeak\Http\Validation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Settings.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Http\Validation
 */
class Settings extends FormRequest
{
    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return string[]
     */
    public function rules()
    {
        return [
            'server_host'             => 'required|string',
            'server_port'             => 'required|numeric|min:1|max:65535',
            'api_base_uri'            => 'required|url',
            'api_key'                 => 'required|string',
            'registration_group_name' => 'required|string',
        ];
    }
}
