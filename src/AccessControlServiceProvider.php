<?php

namespace Back2Lobby\AccessControl;

use Back2Lobby\AccessControl\Exceptions\InvalidRoleableException;
use Back2Lobby\AccessControl\Facades\AccessControlFacade;
use Back2Lobby\AccessControl\Stores\CacheStore;
use Back2Lobby\AccessControl\Stores\SessionStore;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AccessControlServiceProvider extends ServiceProvider
{
    public function provides(): array
    {
        return ['access-control'];
    }

    /**
     * Register services.
     */
    public function register(): void
    {
    }

    public function boot(): void
    {

        // add migrations
        $this->publishMigrations();

        // add configs
        $this->publishConfigs();

        // if (!App::runningInConsole()) {
        // create singleton
        $this->app->singleton('access-control', function ($app) {
            return new AccessControlService(CacheStore::getInstance(), SessionStore::getInstance());
        });

        // setup check point
        $this->app->make(Gate::class)->before(function (Model $user, $authority, array $arguments = []) {
            return $this->accessControlCheck($user, $authority, $arguments);
        });

        // set currently auth user in session store
        Event::listen(Authenticated::class, function (Authenticated $event) {
            $this->app->get('access-control')?->getSessionStore()->setAuthUser($event->user);
        });
        // }
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

    protected function publishConfigs(): void
    {
        $this->publishes([
            __DIR__.'/../config/access.php' => $this->app->configPath().'/access.php',
        ], 'access-control.config');
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
