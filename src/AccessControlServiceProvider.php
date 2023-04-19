<?php

namespace Back2Lobby\AccessControl;

use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Store\StoreService;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\ServiceProvider;

class AccessControlServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('access-control', function ($app) {
            return new AccessControlService(StoreService::getInstance());
        });

        // add migrations
        $this->publishMigrations();

        // setup check point
        $this->app->make(Gate::class)->before(function () {
            return $this->accessControlCheck(...func_get_args());
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Publish the package's migrations.
     *
     * @return void
     */
    protected function publishMigrations()
    {
        $timestamp = date('Y_m_d_His', time());

        $stub = __DIR__.'/../migrations/create_access_control_tables.php';

        $target = $this->app->databasePath().'/migrations/'.$timestamp.'_create_access_control_tables.php';

        $this->publishes([$stub => $target], 'access-control.migrations');
    }

    /**
     * Check whether the user can do this or not
     */
    protected function accessControlCheck($user, $authority, array $arguments = []): bool
    {
        $roleable = isset($arguments[0]) > 0 ? $arguments[0] : null;

        return AccessControlFacade::canUser($user, true)->do($authority, $roleable) ?? false;
    }
}
