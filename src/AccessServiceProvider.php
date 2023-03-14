<?php

namespace Back2Lobby\AccessControl;

use Back2Lobby\AccessControl\Service\AccessControlService;
use Back2Lobby\AccessControl\Store\AccessStoreService;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AccessServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 */
	public function register(): void
	{
        $this->app->singleton("access-store", function () {

            // if access store is cached then no need to make a new one
            return Cache::get('access-store') ?? new AccessStoreService();
        });

		$this->app->singleton("access-control", function ($app) {
			return new AccessControlService($app->make("access-store"));
		});

        $this->publishMigrations();
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

        // setup check point
        $this->app->make(Gate::class)->before(function(){
            return $this->accessControlCheck(...func_get_args());
        });
    }


    /**
     * Check whether the user can do this or not
     *
     * @param $user
     * @param $authority
     * @param array $arguments
     * @return bool
     */
    protected function accessControlCheck($user, $authority, array $arguments = []): bool
    {
        $roleable = isset($arguments[0]) > 0 ? $arguments[0] : null;

        return AccessControl::canUser($user,true)->do($authority,$roleable) ?? false;
    }
}
