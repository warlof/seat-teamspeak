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

namespace Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions;

use Throwable;

/**
 * Class InvalidServerGroupException.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak\Exceptions
 */
class InvalidServerGroupException extends TeamspeakException
{
    /**
     * InvalidServerGroupException constructor.
     *
     * @param string $server_group
     */
    public function __construct(string $server_group)
    {
        parent::__construct(sprintf('Server Group %s is not found.', $server_group));
    }
}
