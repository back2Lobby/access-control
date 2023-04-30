<?php

namespace Back2Lobby\AccessControl;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Store\StoreService;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Route;
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
     * Publish the package's migrations.
     */
    protected function publishMigrations(): void
    {
        $timestamp = date('Y_m_d_His', time());

        $stub = __DIR__.'/../migrations/create_access_control_tables.php';

        $target = $this->app->databasePath().'/migrations/'.$timestamp.'_create_access_control_tables.php';

        $this->publishes([$stub => $target], 'access-control.migrations');
    }

    /**
     * Check whether the user can do this or not
     *
     * @throws InvalidRoleableException
     */
    protected function accessControlCheck($user, $authority, array $arguments = []): bool
    {
        $roleable = isset($arguments[0]) > 0 ? $arguments[0] : null;

        // if roleable is string then assuming it's currently authorizing for a route
        if (is_string($roleable) && class_exists($roleable) && Route::current()) {
            $roleableId = Route::current()->parameter(strtolower(class_basename($roleable)));
            if ($roleableId) {
                $roleable = $roleable::find($roleableId);
            } else {
                throw new InvalidRoleableException("The `id` of the roleable `$roleable` was not found in the route URL. Please make sure to pass the `id` of the `$roleable` instance through the URL parameter.");
            }
        }

        return AccessControlFacade::canUser($user, true)->do($authority, $roleable) ?? false;
    }
}
