<?php

namespace Back2Lobby\AccessControl;

use Back2Lobby\AccessControl\Service\AccessControlService;
use Back2Lobby\AccessControl\Store\AccessStoreService;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AccessServiceProvider extends ServiceProvider
{
	/**
	 * Register services.
	 */
	public function register(): void
	{
        $this->app->singleton("access-store", function () {
            return new AccessStoreService();
        });

		$this->app->singleton("access-control", function (Application $app) {
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
    }
}
