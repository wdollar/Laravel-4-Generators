<?php namespace Vsch\Generators;

use Illuminate\Support\Facades\Config;
use Vsch\Generators\Commands;
use Vsch\Generators\Generators;
use Vsch\Generators\Cache;
use Illuminate\Support\ServiceProvider;

class GeneratorsServiceProvider extends ServiceProvider
{
    const GENERATOR_ROUTE_TAG = '// Generators:insert new routes here';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * @param mixed  $files   partial files path for the template.
     *                        search for it in the package directory config
     *                        if not found in our template directory. If the files path is an
     *                        empty string then return the base template path in the configuration
     *                        for the package. NOTE: this path may not contain all the template files,
     *                        only the one's the user decided to override.
     *
     * @param string $suffix  text to append to $files or every item in $files if it is an array
     *
     * @return string returns the name in the base template path, if not found in the package path.
     *
     */
    public static
    function getTemplatePath($files = null, $suffix = '')
    {
        $packagePath = Config::get('generators::generators.templates', '');
        $hardPath = __DIR__ . '/../../config/templates/';
        $isDir = $suffix === '/';

        if ((is_null($files) || $files === '') && (is_null($suffix) || $suffix === ''))
        {
            $packagePath = str_finish($packagePath, "/");

            return $packagePath ? $packagePath[0] : $hardPath;
        }

        // we search
        $searchPath = [];
        if ($packagePath) $searchPath = array_merge($searchPath, $packagePath);
        $searchPath[] = $hardPath;

        foreach ($searchPath as $path)
        {
            if (!is_array($files)) $files = [$files];
            if ($isDir)
            {
                foreach ($files as $file)
                {
                    $path = str_finish($path, "/") . str_finish($file, "/");
                    if (!(is_null($suffix) || $suffix === '')) $path .= str_finish($suffix, "/");
                    if (is_dir($path)) break 2;
                }
            }
            else
            {
                foreach ($files as $file)
                {
                    $path = str_finish($path, "/") . $file;
                    if (!(is_null($suffix) || $suffix === '')) $path .= $suffix;
                    if (file_exists($path)) break 2;
                }
            }
        }

        // even if not found we return its base path location
        return $path;
    }

    public
    function boot()
    {
        $this->package('vsch/generators');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public
    function register()
    {
        $this->registerModelGenerator();
        $this->registerControllerGenerator();
        $this->registerTestGenerator();
        $this->registerResourceGenerator();
        $this->registerScaffoldGenerator();
        $this->registerViewGenerator();
        $this->registerMigrationGenerator();
        $this->registerPivotGenerator();
        $this->registerSeedGenerator();
        $this->registerFormDumper();

        $this->commands('generate.model', 'generate.controller', 'generate.test', 'generate.scaffold', 'generate.resource', 'generate.view', 'generate.migration', 'generate.seed', 'generate.form', 'generate.pivot');
    }

    /**
     * Register generate:model
     *
     * @return Commands\ModelGeneratorCommand
     */
    protected
    function registerModelGenerator()
    {
        $this->app['generate.model'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\ModelGenerator($app['files'], $cache);

            return new Commands\ModelGeneratorCommand($generator);
        });
    }

    /**
     * Register generate:controller
     *
     * @return Commands\ControllerGeneratorCommand
     */
    protected
    function registerControllerGenerator()
    {
        $this->app['generate.controller'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\ControllerGenerator($app['files'], $cache);

            return new Commands\ControllerGeneratorCommand($generator);
        });
    }

    /**
     * Register generate:test
     *
     * @return Commands\TestGeneratorCommand
     */
    protected
    function registerTestGenerator()
    {
        $this->app['generate.test'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\TestGenerator($app['files'], $cache);

            return new Commands\TestGeneratorCommand($generator);
        });
    }

    /**
     * Register generate:view
     *
     * @return Commands\ViewGeneratorCommand
     */
    protected
    function registerViewGenerator()
    {
        $this->app['generate.view'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\ViewGenerator($app['files'], $cache);

            return new Commands\ViewGeneratorCommand($generator);
        });
    }

    /**
     * Register generate:scaffold
     *
     * @return Commands\ScaffoldGeneratorCommand
     */
    protected
    function registerScaffoldGenerator()
    {
        $this->app['generate.scaffold'] = $this->app->share(function ($app)
        {
            $generator = new Generators\ResourceGenerator($app['files']);
            $cache = new Cache($app['files']);

            return new Commands\ScaffoldGeneratorCommand($generator, $cache);
        });
    }

    /**
     * Register generate:scaffold
     *
     * @return Commands\ScaffoldGeneratorCommand
     */
    protected
    function registerResourceGenerator()
    {
        $this->app['generate.resource'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\ResourceGenerator($app['files'], $cache);

            return new Commands\ResourceGeneratorCommand($generator, $cache);
        });
    }

    /**
     * Register generate:migration
     *
     * @return Commands\MigrationGeneratorCommand
     */
    protected
    function registerMigrationGenerator()
    {
        $this->app['generate.migration'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\MigrationGenerator($app['files'], $cache);

            return new Commands\MigrationGeneratorCommand($generator);
        });
    }

    /**
     * Register generate:pivot
     *
     * @return Commands\PivotGeneratorCommand
     */
    protected
    function registerPivotGenerator()
    {
        $this->app['generate.pivot'] = $this->app->share(function ($app)
        {
            return new Commands\PivotGeneratorCommand;
        });
    }

    /**
     * Register generate:seed
     *
     * @return Commands\MigrationGeneratorCommand
     */
    protected
    function registerSeedGenerator()
    {
        $this->app['generate.seed'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\SeedGenerator($app['files'], $cache);

            return new Commands\SeedGeneratorCommand($generator);
        });
    }

    /**
     * Register generate:migration
     *
     * @return Commands\MigrationGeneratorCommand
     */
    protected
    function registerFormDumper()
    {
        $this->app['generate.form'] = $this->app->share(function ($app)
        {
            $gen = new Generators\FormDumperGenerator($app['files'], new \Mustache_Engine);

            return new Commands\FormDumperCommand($gen);
        });
    }
}
