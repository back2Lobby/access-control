<?php

namespace Back2Lobby\AccessControl\Tests;

use Back2Lobby\AccessControl\AccessControlServiceProvider;
use Back2Lobby\AccessControl\Store\StoreService;
use Closure;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class BaseTestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // specifying custom factories locations
        Factory::guessFactoryNamesUsing(function (string $modelName) {

            // just add here all the namespaces where the factories are
            $namespaces = ['Back2Lobby\\AccessControl\\Factories\\', 'Back2Lobby\\AccessControl\\Tests\\Factories\\'];

            $modelName = Str::afterLast($modelName, '\\');

            $matches = array_values(array_filter(
                array_map(fn ($namespace) => $namespace.$modelName.'Factory', $namespaces),
                fn ($factoryClassName) => class_exists($factoryClassName)
            ));

            // ensuring all the factory names end with `Factory`
            return $matches[0] ?? $modelName.'Factory';
        });

        // clear the store cache for every test
        StoreService::getInstance()->clearCache();
        StoreService::getInstance()->reset();
    }

    protected function getPackageProviders($app): array
    {
        return [
            AccessControlServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageAliases($app): array
    {
        return [
            'AccessControl' => 'Back2Lobby\\AccessControl\\Facades\\AccessControlFacade',
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadLaravelMigrations(['--database' => 'testbench']);
        $this->loadMigrationsFrom(dirname(__DIR__, 1).'/migrations');
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
    }

    /**
     * Assert that given exception is thrown in the closure passed
     * - Unlike `expectException` method from phpunit, it allows testing multiple exceptions in a
     * single test function
     */
    public function assertException(string $targetExceptionName, Closure $targetClosure, string $targetExceptionMessage = null): void
    {
        $caughtExceptionName = null;
        $caughtExceptionMessage = null;

        try {
            $targetClosure();
        } catch (\Exception $e) {
            $caughtExceptionName = get_class($e);
            $caughtExceptionMessage = $e->getMessage();
        }

        $this->assertSame($targetExceptionName, $caughtExceptionName, is_null($caughtExceptionName) ?
            "Failed asserting that expected exception `$targetExceptionName` was thrown" :
            "Failed asserting that expected exception `$targetExceptionName` was thrown. Instead the exception thrown was `$caughtExceptionName`");

        if ($targetExceptionMessage) {
            $this->assertSame($targetExceptionMessage, $caughtExceptionMessage);
        }
    }

    /**
     * Assert that both of the given array/collection have same values and have same elements regardless of the order
     *
     * <b>NOTE: It doesn't consider matching keys</b>
     *
     *
     * */
    public function assertSameArray(array|Collection $array1, array|Collection $array2): void
    {
        if (count($array1) !== count($array2)) {
        self::fail('Failed asserting that both arrays are same because they have different length');
        }

        if (collect($array1)->diff($array2)->count() !== 0) {
        self::fail('Failed asserting that both arrays are same');
        }

        $this->assertSame(true, true);
    }
}
