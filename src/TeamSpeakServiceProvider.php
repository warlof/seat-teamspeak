<?php

namespace Seat\Warlof\Teamspeak;

use Illuminate\Support\ServiceProvider;
use Seat\Warlof\Teamspeak\Commands\TeamspeakLogsClear;
use Seat\Warlof\Teamspeak\Commands\TeamspeakUpdate;
use Seat\Warlof\Teamspeak\Commands\TeamspeakGroupsUpdate;

class TeamSpeakServiceProvider extends ServiceProvider
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

    public function addCommands()
    {
        $this->commands([
            TeamspeakUpdate::class,
            TeamspeakGroupsUpdate::class,
            TeamspeakLogsClear::class
        ]);
    }
    
    public function addTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/lang', 'teamspeak');
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
            __DIR__ . '/resources/assets'     => public_path('web'),
            __DIR__ . '/database/migrations/' => database_path('migrations')
        ]);
    }
}
