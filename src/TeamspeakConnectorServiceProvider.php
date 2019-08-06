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

namespace Warlof\Seat\Connector\Drivers\Teamspeak;

use Seat\Services\AbstractSeatPlugin;
use Warlof\Seat\Connector\Drivers\Teamspeak\Commands\Upgrade;

/**
 * Class TeamspeakConnectorServiceProvider.
 *
 * @package Warlof\Seat\Connector\Drivers\Teamspeak
 */
class TeamspeakConnectorServiceProvider extends AbstractSeatPlugin
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->addRoutes();
        $this->addViews();
        $this->addTranslations();
        $this->addUpgradeCommand();
        $this->addUpgradeMigrations();
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
            __DIR__ . '/Config/seat-connector.config.php', 'seat-connector.drivers.teamspeak');
    }

    private function addRoutes()
    {
        if (! $this->app->routesAreCached()) {
            include __DIR__ . '/Http/routes.php';
        }
    }

    private function addViews()
    {
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-connector-teamspeak');
    }

    private function addTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'seat-connector-teamspeak');
    }

    private function addUpgradeCommand()
    {
        $this->commands([
            Upgrade::class,
        ]);
    }

    private function addUpgradeMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    /**
     * Return an URI to a CHANGELOG.md file or an API path which will be providing changelog history.
     *
     * @example https://raw.githubusercontent.com/eveseat/seat/master/LICENSE
     * @exemple https://api.github.com/repos/eveseat/web/releases
     *
     * @return string|null
     */
    public function getChangelogUri(): ?string
    {
        return 'https://raw.githubusercontent.com/warlof/seat-teamspeak/master/CHANGELOG.md';
    }

    /**
     * Return the plugin public name as it should be displayed into settings.
     *
     * @example SeAT Web
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Teamspeak Connector';
    }

    /**
     * Return the plugin repository address.
     *
     * @example https://github.com/eveseat/web
     *
     * @return string
     */
    public function getPackageRepositoryUrl(): string
    {
        return 'https://github.com/warlof/seat-teamspeak';
    }

    /**
     * Return the plugin technical name as published on package manager.
     *
     * @example web
     *
     * @return string
     */
    public function getPackagistPackageName(): string
    {
        return 'seat-teamspeak';
    }

    /**
     * Return the plugin vendor tag as published on package manager.
     *
     * @example eveseat
     *
     * @return string
     */
    public function getPackagistVendorName(): string
    {
        return 'warlof';
    }

    /**
     * Return the plugin installed version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return config('teamspeak.config.version');
    }
}
