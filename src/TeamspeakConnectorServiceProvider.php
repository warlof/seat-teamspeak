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

namespace Warlof\Seat\Connector\Teamspeak;

use Illuminate\Support\ServiceProvider;
use Warlof\Seat\Connector\Teamspeak\Commands\TeamspeakGroupSync;
use Warlof\Seat\Connector\Teamspeak\Commands\TeamspeakLogsClear;
use Warlof\Seat\Connector\Teamspeak\Commands\TeamspeakUserPolicy;

class TeamspeakConnectorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addCommands();
        $this->addRoutes();
        $this->addViews();
        $this->addPublications();
        $this->addTranslations();
    }

    public function addCommands()
    {
        $this->commands([
            TeamspeakUserPolicy::class,
            TeamspeakGroupSync::class,
            TeamspeakLogsClear::class
        ]);
    }

    public function addRoutes()
    {
        if (!$this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    public function addViews()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'teamspeak');
    }

    public function addPublications()
    {
        $this->publishes([
            __DIR__ . '/database/migrations/' => database_path('migrations')
        ]);
    }

    public function addTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'teamspeak');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/teamspeak.config.php', 'teamspeak.config');

        $this->mergeConfigFrom(
            __DIR__ . '/Config/teamspeak.permissions.php', 'web.permissions');

        $this->mergeConfigFrom(
            __DIR__ . '/Config/package.sidebar.php', 'package.sidebar');
    }
}
