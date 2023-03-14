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
	}

	/**
	 * Bootstrap services.
	 */
	public function boot(): void
	{
		//
	}
}
