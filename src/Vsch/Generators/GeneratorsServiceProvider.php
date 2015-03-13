<?php namespace Vsch\Generators;

use Illuminate\Support\Facades\Config;
use Vsch\Generators\Commands;
use Vsch\Generators\Generators;
use Vsch\Generators\Cache;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Pluralizer;

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
                    if ($file === '/') $file = '';
                    $trypath = str_finish($path, "/") . ($file !== '' ? str_finish($file, "/") : '');
                    if (!(is_null($suffix) || $suffix === '')) $trypath .= str_finish($suffix, "/");
                    if (is_dir($trypath))
                    {
                        $path = $trypath;
                        break 2;
                    }
                }
            }
            else
            {
                foreach ($files as $file)
                {
                    if ($file === '/') $file = '';
                    $trypath = str_finish($path, "/") . $file;
                    if (!(is_null($suffix) || $suffix === '')) $trypath .= $suffix;
                    if (file_exists($trypath))
                    {
                        $path = $trypath;
                        break 2;
                    }
                }
            }
        }

        // even if not found we return its base path location
        return $path;
    }

    public static
    function getModelVars($modelName)
    {
        $camelModel = $modelName;  // blockedEmail
        $camelModels = Pluralizer::plural($camelModel);  // blockedEmails
        $CamelModel = strtoupper(substr($camelModel, 0, 1)) . substr($camelModel, 1);  // blockedEmail
        $CamelModels = strtoupper(substr($camelModels, 0, 1)) . substr($camelModels, 1);  // blockedEmail
        $model = strtolower($camelModel);                                 // blockedemail
        $models = strtolower($camelModels);                       // blockedemails
        $MODEL = strtoupper($camelModel);                                 // blockedemail
        $MODELS = strtoupper($camelModels);                       // blockedemails
        $Model = strtoupper(substr($camelModel, 0, 1)) . substr($camelModel, 1);    // BlockedEmail
        $Models = strtoupper(substr($camelModels, 0, 1)) . substr($camelModels, 1);    // BlockedEmail
        $snake_model = snake_case($camelModel);
        $snake_models = snake_case($camelModels);
        $Snake_Model = str_replace(' ', '_', ucwords(snake_case($camelModel, ' ')));
        $Snake_Models = str_replace(' ', '_', ucwords(snake_case($camelModels, ' ')));
        $SNAKE_MODEL = strtoupper($snake_model);
        $SNAKE_MODELS = strtoupper($snake_models);
        $dash_model = snake_case($camelModel, '-');
        $dash_models = snake_case($camelModels, '-');
        $DASH_MODEL = strtoupper($dash_model);
        $DASH_MODELS = strtoupper($dash_models);
        $Dash_Model = str_replace(' ', '-', ucwords(snake_case($camelModel, ' ')));
        $Dash_Models = str_replace(' ', '-', ucwords(snake_case($camelModels, ' ')));
        $modelVars = [
            'camelModel' => $camelModel,
            'camelModels' => $camelModels,
            'CamelModel' => $CamelModel,
            'CamelModels' => $CamelModels,
            'model' => $model,
            'models' => $models,
            'MODEL' => $MODEL,
            'MODELS' => $MODELS,
            'Model' => $Model,
            'Models' => $Models,
            'snake_model' => $snake_model,
            'snake_models' => $snake_models,
            'Snake_Model' => $Snake_Model,
            'Snake_Models' => $Snake_Models,
            'SNAKE_MODEL' => $SNAKE_MODEL,
            'SNAKE_MODELS' => $SNAKE_MODELS,
            'dash-model' => $dash_model,
            'dash-models' => $dash_models,
            'Dash-Model' => $Dash_Model,
            'Dash-Models' => $Dash_Models,
            'DASH-MODEL' => $DASH_MODEL,
            'DASH-MODELS' => $DASH_MODELS,
        ];

        return $modelVars;
    }

    public static
    function replaceModelVars($text, $modelVars, $varPrefix = '{{', $varSuffix = '}}')
    {
        $vars = array_keys($modelVars);
        array_walk($vars, function (&$var) use ($varPrefix, $varSuffix)
        {
            $var = $varPrefix . $var . $varSuffix;
        });

        $text = str_replace($vars, array_values($modelVars), $text);
        return $text;
    }

    public static
    function replaceTemplateLines($template, $fieldKey, \Closure $closure)
    {
        while (($pos = strpos($template, $fieldKey)) !== false)
        {
            // grab the line that contains
            $startPos = strrpos($template, "\n", -(strlen($template) - $pos));
            if ($startPos === false) $startPos = -1;

            $endPos = strpos($template, "\n", $pos);
            if ($endPos === false) $endPos = strlen($template) + 1;

            $line = substr($template, $startPos + 1, $endPos - $startPos - 1);
            $line = str_replace('{{line:eol}}', "\n", $line);

            $fieldValue = $closure($line, $fieldKey);

            $template = substr($template, 0, $startPos + 1) . $fieldValue . substr($template, $endPos + 1);
        }

        return $template;
    }

    public static
    function isFieldHintOption($option)
    {
        return array_search($option, ['hidden', 'guarded', 'notrail', 'textarea',]) !== false;
    }

    public static
    function fieldTypeOptions($typeText)
    {
        $options = explode(':', $typeText, 2);
        $type = $options[0];
        $options = count($options) > 1 ? $options[1] : '';
        return array($type, $options);
    }

    public static
    function filterFieldHavingOption($fields, $optionName)
    {
        $keep = [];
        foreach ($fields as $name => $typeText)
        {
            list($type, $options) = self::fieldTypeOptions($typeText);
            $exp = "/\b${optionName}\b/";
            if (preg_match($exp, $options)) continue;
            $keep[$name] = $typeText;
        }
        return $keep;
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
        $this->registerTranslationsGenerator();
        $this->registerMigrationGenerator();
        $this->registerPivotGenerator();
        $this->registerSeedGenerator();
        $this->registerFormDumper();

        $this->commands('generate.model', 'generate.translations', 'generate.controller', 'generate.test', 'generate.scaffold', 'generate.resource', 'generate.view', 'generate.migration', 'generate.seed', 'generate.form', 'generate.pivot');
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
     * Register generate:translations
     *
     * @return Commands\ModelGeneratorCommand
     */
    protected
    function registerTranslationsGenerator()
    {
        $this->app['generate.translations'] = $this->app->share(function ($app)
        {
            $cache = new Cache($app['files']);
            $generator = new Generators\TranslationsGenerator($app['files'], $cache);

            return new Commands\TranslationsGeneratorCommand($generator);
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
