<?php namespace Vsch\Generators;

use Illuminate\Support\Pluralizer;
use Illuminate\Support\ServiceProvider;
use Vsch\Generators\Commands;
use Vsch\Generators\Generators;

require_once(__DIR__ . '/scopedexplode.php');

class GeneratorsServiceProvider extends ServiceProvider
{
    const PACKAGE = 'generators';

    // Laravel 5
    const LARAVEL_VERSION = '5';
    const CONTROLLER_PREFIX = '\\';
    const PUBLIC_PREFIX = '/vendor/';
    const BLADE_WRAP_SAFE_OPEN = '{{';
    const BLADE_WRAP_SAFE_CLOSE = '}}';
    const BLADE_WRAP_RAW_OPEN = '{!!';
    const BLADE_WRAP_RAW_CLOSE = '!!}';

    // Laravel 4
    //const LARAVEL_VERSION = '4';
    //const CONTROLLER_PREFIX = '';
    //const PUBLIC_PREFIX = '/packages/';
    //const BLADE_WRAP_SAFE_OPEN = '{{{';
    //const BLADE_WRAP_SAFE_CLOSE = '}}}';
    //const BLADE_WRAP_RAW_OPEN = '{{';
    //const BLADE_WRAP_RAW_CLOSE = '}}';

    const GENERATOR_ROUTE_TAG = '// Generators:insert new routes here';

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public static
    function uniquify($filePath)
    {
        //$filePath = '/www/sites/vladsch/database/migrations/2015_10_17_030224_*_create_license_agent_versions_table.php';
        $base_path = dirname($filePath);
        $filename = substr($filePath, strlen($base_path) + 1);

        $pos = strpos($filename, '*');
        $name = substr($filename, 0, $pos + 1);

        assert($pos !== false, "pattern '*' must exist in $filePath");

        $maxMatch = 0;

        if ($handle = opendir($base_path)) {
            while (false !== ($entry = readdir($handle))) {
                if (fnmatch($name, $entry, FNM_PERIOD)) {
                    // this one matches, lets extract the stuff matched by *
                    if (preg_match('/(\d+)/', substr($entry, $pos + 1), $matches)) {
                        $found = $matches[1];
                        //print("Found $entry : value $found\n");
                        if ($maxMatch < $found) {
                            $maxMatch = intval($found);
                        }
                    }
                }
            }
            closedir($handle);
        }

        $maxMatch = sprintf("%02d", $maxMatch + 1);

        return str_replace('*', $maxMatch, $filePath);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public
    function register()
    {
        // Register the config publish path
        $configPath = __DIR__ . '/../config/' . self::PACKAGE . '.php';
        $this->mergeConfigFrom($configPath, self::PACKAGE);
        $this->publishes([$configPath => config_path(self::PACKAGE . '.php')], 'config');

        $this->app->singleton($command = 'command.generate.controller', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\ControllerGenerator($app['files'], $cache);

            return new Commands\ControllerGeneratorCommand($generator);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.form', function ($app) {
            $gen = new Generators\FormDumperGenerator($app['files'], new \Mustache_Engine);

            return new Commands\FormDumperCommand($gen);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.migration', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\MigrationGenerator($app['files'], $cache);

            return new Commands\MigrationGeneratorCommand($generator);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.model', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\ModelGenerator($app['files'], $cache);

            return new Commands\ModelGeneratorCommand($generator);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.pivot', function ($app) {
            return new Commands\PivotGeneratorCommand;
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.resource', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\ResourceGenerator($app['files'], $cache);

            return new Commands\ResourceGeneratorCommand($generator, $cache);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.scaffold', function ($app) {
            $generator = new Generators\ResourceGenerator($app['files']);
            $cache = new Cache($app['files']);

            return new Commands\ScaffoldGeneratorCommand($generator, $cache);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.seed', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\SeedGenerator($app['files'], $cache);

            return new Commands\SeedGeneratorCommand($generator);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.test', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\TestGenerator($app['files'], $cache);

            return new Commands\TestGeneratorCommand($generator);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.translations', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\TranslationsGenerator($app['files'], $cache);

            return new Commands\TranslationsGeneratorCommand($generator);
        });
        $this->commands($command);

        $this->app->singleton($command = 'command.generate.view', function ($app) {
            $cache = new Cache($app['files']);
            $generator = new Generators\ViewGenerator($app['files'], $cache);

            return new Commands\ViewGeneratorCommand($generator);
        });
        $this->commands($command);
    }

    public
    function boot()
    {
    }

    /**
     * @param $srcType
     *
     * @return mixed
     */
    public static
    function getSrcTypePath($srcType = null)
    {
        $config = \Config::get(GeneratorsServiceProvider::PACKAGE, null);

        if (!$config || !array_key_exists('dir_map', $config)) {
            assert(false, "dir_map entry is missing from generator.php configuration file.");
        }

        $dir_map = $config['dir_map'];

        if ($srcType === null) return $dir_map;

        if (!array_key_exists($srcType, $dir_map)) {
            assert(false, "dir_map is missing entry for '$srcType'");
        }

        if (!array_key_exists('app', $dir_map[$srcType]) || !array_key_exists('bench', $dir_map[$srcType])) {
            assert(false, "dir_map['$srcType'] is missing entries for 'app' and 'bench'");
            return $dir_map;
        }
        return $dir_map[$srcType];
    }

    private static
    function vendorPackage($package)
    {
        $parts = explode('/', $package, 2);
        return ucfirst($parts[0]) . '/' . str_replace(' ', '', ucwords(str_replace('-', ' ', $parts[1]))) . '/';
    }

    public static
    function getSrcPath($srcType, $package = null)
    {
        $dir_map = GeneratorsServiceProvider::getSrcTypePath();

        if ($package) {
            $benchDir = $dir_map[$srcType]['bench'];
            $srcPath = $benchDir;
            if ($benchDir !== '') {
                $srcPath = str_replace('{{vendor/package}}', self::vendorPackage($package), $srcPath);
                $srcPath = str_replace('{{Vendor/Package}}', $package, $srcPath);
                $srcPath = 'workbench/' . $package . '/' . $srcPath;
            }
        }
        else {
            $appDir = $dir_map[$srcType]['app'];
            $srcPath = $appDir;
        }

        $srcPath = end_with(base_path(), '/') . $srcPath;
        return $srcPath;
    }

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
        $packagePath = self::LARAVEL_VERSION === '5' ? \Config::get('generators.templates') : \Config::get('generators::generators.templates', '');

        $hardPath = __DIR__ . '/../config/templates/';
        $isDir = $suffix === '/';

        if (($files === null || $files === '') && ($suffix === null || $suffix === '')) {
            $packagePath = str_finish($packagePath, "/");

            return $packagePath ? $packagePath[0] : $hardPath;
        }

        // we search
        $searchPath = [];
        if ($packagePath) $searchPath = array_merge($searchPath, $packagePath);
        $searchPath[] = $hardPath;

        foreach ($searchPath as $path) {
            if (!is_array($files)) $files = [$files];
            if ($isDir) {
                foreach ($files as $file) {
                    if ($file === '/') $file = '';
                    $trypath = str_finish($path, "/") . ($file !== '' ? str_finish($file, "/") : '');
                    if (!($suffix === null || $suffix === '')) $trypath .= str_finish($suffix, "/");
                    if (is_dir($trypath)) {
                        $path = $trypath;
                        break 2;
                    }
                }
            }
            else {
                foreach ($files as $file) {
                    if ($file === '/') $file = '';
                    $trypath = str_finish($path, "/") . $file;
                    if (!($suffix === null || $suffix === '')) $trypath .= $suffix;
                    if (file_exists($trypath)) {
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
    function getModelVars($originalModelName)
    {
        // figure out the format of the modelName: has _ then snake, has - then dash, has space then space, else assume CamelCase
        $modelName = $originalModelName;
        if (str_contains($modelName, '_')) {
            $modelName = Pluralizer::singular(str_replace(' ', '', ucwords(str_replace('_', ' ', $modelName))));
        }
        else if (str_contains($modelName, '-')) {
            $modelName = Pluralizer::singular(str_replace(' ', '', ucwords(str_replace('-', ' ', $modelName))));
        }
        else if (str_contains($modelName, ' ')) {
            $modelName = Pluralizer::singular(str_replace(' ', '', ucwords($modelName)));
        }
        else {
            $modelName = Pluralizer::singular($modelName);
        }

        $camelModel = strtolower(substr($modelName, 0, 1)) . substr($modelName, 1);  // blockedEmail
        $camelModels = Pluralizer::plural($camelModel);  // blockedEmails
        $CamelModel = strtoupper(substr($camelModel, 0, 1)) . substr($camelModel, 1);  // BlockedEmail
        $CamelModels = strtoupper(substr($camelModels, 0, 1)) . substr($camelModels, 1);  // BlockedEmails

        $model = strtolower($camelModel); // blockedemail
        $models = strtolower($camelModels); // blockedemails
        $Model = $CamelModel;               // BlockedEmail
        $Models = $CamelModels;             // BlockedEmails
        $MODEL = strtoupper($camelModel);   // BLOCKEDEMAIL
        $MODELS = strtoupper($camelModels); // BLOCKEDEMAILS

        $snake_model = snake_case($camelModel);
        $snake_models = snake_case($camelModels);
        $Snake_Model = str_replace(' ', '_', ucwords(snake_case($camelModel, ' ')));
        $Snake_Models = str_replace(' ', '_', ucwords(snake_case($camelModels, ' ')));
        $SNAKE_MODEL = strtoupper($snake_model);
        $SNAKE_MODELS = strtoupper($snake_models);

        $dash_model = str_replace('_', '-', $snake_model);
        $dash_models = str_replace('_', '-', $snake_models);
        $Dash_Model = str_replace('_', '-', $Snake_Model);
        $Dash_Models = str_replace('_', '-', $Snake_Models);
        $DASH_MODEL = str_replace('_', '-', $SNAKE_MODEL);
        $DASH_MODELS = str_replace('_', '-', $SNAKE_MODELS);

        $space_model = str_replace('-', ' ', $dash_model);
        $space_models = str_replace('-', ' ', $dash_models);
        $Space_Model = str_replace('-', ' ', $Dash_Model);
        $Space_Models = str_replace('-', ' ', $Dash_Models);
        $SPACE_MODEL = str_replace('-', ' ', $DASH_MODEL);
        $SPACE_MODELS = str_replace('-', ' ', $DASH_MODELS);

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
            'space model' => $space_model,
            'space models' => $space_models,
            'Space Model' => $Space_Model,
            'Space Models' => $Space_Models,
            'SPACE MODEL' => $SPACE_MODEL,
            'SPACE MODELS' => $SPACE_MODELS,
        ];

        return $modelVars;
    }

    public static
    function replaceModelVars($text, $modelVars, $varPrefix = '{{', $varSuffix = '}}')
    {
        $vars = array_keys($modelVars);
        array_walk($vars, function (&$var) use ($varPrefix, $varSuffix) {
            $var = $varPrefix . $var . $varSuffix;
        });

        $text = str_replace($vars, array_values($modelVars), $text);
        return $text;
    }

    public static
    function replaceModel($modelName, $text)
    {
        $modelVars = self::getModelVars($modelName);
        return self::replaceModelVars($text, $modelVars);
    }

    public static
    function replaceTemplateLines($template, $fieldKey, \Closure $closure)
    {
        while (($pos = strpos($template, $fieldKey)) !== false) {
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

    /**
     * @param $name    string   field name
     * @param $options array|string field options
     *
     * @return array|null   return the foreign model vars or null if not a foreign field reference. If the name ends in _id then
     *                      will use the part before _id as snake_case singular form of the table being referenced, unless foreign() option
     *                      is provided in the field, in which case will use the table name from the option.
     *
     *                      if foreign() option is of the form foreign(table,id,name) then the id part will be added to foreign model vars
     *                      under the 'id' key otherwise 'id' is added as the foreign id column and name part under 'name', otherwise 'id' will be
     *                      used as the foreign display column.
     */
    public static
    function getForeignModelVars($name, $options)
    {
        $foreignTable = null;
        $foreignId = null;
        $foreignName = null;
        $sansId = null;
        if (substr($name, -3) === '_id') {
            // assume foreign key
            $foreignTable = substr($name, 0, -3);
            $sansId = $foreignTable;
        }

        if (!is_array($options)) $options = array($options);

        foreach ($options as $option) {
            if (starts_with($option, 'foreign(')) {
                $pos = strrpos($option, ')');
                if ($pos === false) $pos = strlen($option);
                $foreignTable = explode(',',substr($option, strlen('foreign('), $pos - strlen('foreign(')));
                if ((count($foreignTable) > 1)) $foreignId = $foreignTable[1];
                if ((count($foreignTable) > 2)) $foreignName = $foreignTable[2];
                $foreignTable = $foreignTable[0];
                if ($sansId === null) $sansId = $foreignTable;
                break;
            }
        }

        if ($foreignTable) {
            $foreignModelVars = GeneratorsServiceProvider::getModelVars($foreignTable);
            $foreignModelVars['id'] = $foreignId ?: 'id';
            $foreignModelVars['name'] = $foreignName ?: 'id';
            $foreignModelVars['table'] = $foreignTable;
            $foreignModelVars['field'] = $name;
            $foreignModelVars['field_no_id'] = $sansId;
            return $foreignModelVars;
        }

        return null;
    }

    public static
    function getRelationsModelVarsList($fields)
    {
        $relationModelList = [];
        foreach ($fields as $field) {
            // add foreign keys
            $name = $field->name;
            $options = $field->options;

            $foreignModelVars = GeneratorsServiceProvider::getForeignModelVars($name, $options);
            if ($foreignModelVars) $relationModelList[$name] = $foreignModelVars;
        }
        return $relationModelList;
    }

    public static
    function isFieldHintOption($option)
    {
        $optionBare = explode('(', $option, 2)[0];
        return array_search($optionBare, [
            'hidden',
            'guarded',
            'bitset',
            'notrail',
            'notrailonly',
            'textarea',
            'index',
            'keyindex',
            'unique',
            'primary',
            'foreign',
            'ondelete',
            'rule',
            'auto',
        ]) !== false;
    }

    public static
    function splitFields($fieldsText, $wantObjArray = false)
    {
        if (is_array($fieldsText)) $fieldsText = implode(',', $fieldsText);

        $openScopes = null;
        if ($wantObjArray !== false) {
            $fields = scopedExplode([',', ':'], [
                '(' => ')',
                '[' => ']',
                '{' => '}',
            ], $fieldsText, null, SCOPED_EXPLODE_TRIM | ($wantObjArray !== true ? $wantObjArray : SCOPED_EXPLODE_WANT_OBJ_ASSOC), $openScopes);
        }
        else {
            $fields = scopedExplode(',', [
                '(' => ')',
                '[' => ']',
                '{' => '}',
            ], $fieldsText, null, SCOPED_EXPLODE_TRIM, $openScopes);
        }
        return $fields;
    }

    public static
    function fieldNameTypeOptions($fieldText, $splitOptions = false)
    {
        $openScopes = null;
        $options = scopedExplode(':', [
            '(' => ')',
            '[' => ']',
            '{' => '}',
        ], $fieldText, null, SCOPED_EXPLODE_TRIM | $splitOptions ? 0 : 3, $openScopes);
        $name = array_shift($options);
        $type = array_shift($options);
        if (!$splitOptions) $options = array_shift($options);
        return array($name, $type, $options);
    }

    public static
    function fieldTypeOptions($typeText, $splitOptions = false)
    {
        $openScopes = null;
        $options = scopedExplode(':', [
            '(' => ')',
            '[' => ']',
            '{' => '}',
        ], $typeText, null, SCOPED_EXPLODE_TRIM | $splitOptions ? 0 : 2, $openScopes);
        $type = array_shift($options);
        if (!$splitOptions) $options = array_shift($options);
        return array($type, $options);
    }

    public static
    function filterFieldHavingOption($fields, $optionName)
    {
        $keep = [];
        foreach ($fields as $name => $typeText) {
            list($type, $options) = self::fieldTypeOptions($typeText);
            $exp = "/\\b${optionName}\\b/";
            if (preg_match($exp, $options)) continue;
            $keep[$name] = $typeText;
        }
        return $keep;
    }

    public static
    function mapFieldTypes($fields)
    {
        $fields = preg_replace('/,\s+/', ',', $fields);
        $fields = preg_replace('/\\bint\\b/', 'integer', $fields);
        $fields = preg_replace('/\\btinyint\\b/', 'tinyInteger', $fields);
        $fields = preg_replace('/\\btiny\\b/', 'tinyInteger', $fields);
        $fields = preg_replace('/\\bsmallint\\b/', 'smallInteger', $fields);
        $fields = preg_replace('/\\bmedint\\b/', 'mediumInteger', $fields);
        $fields = preg_replace('/\\bmediumint\\b/', 'mediumInteger', $fields);
        $fields = preg_replace('/\\bbigint\\b/', 'bigInteger', $fields);
        $fields = preg_replace('/\\bbool\\b/', 'boolean', $fields);
        $fields = preg_replace('/\\bdatetime\\b/', 'dateTime', $fields);
        return $fields;
    }

    public static
    function isFieldBoolean($typeText)
    {
        //$table->boolean('confirmed')
        list($type, $options) = self::fieldTypeOptions($typeText);
        return $type === 'boolean';
    }

    public static
    function isFieldDateTimeType($typeText)
    {
        //$table->boolean('confirmed')
        list($type, $options) = self::fieldTypeOptions($typeText);
        return str_starts_with($type, ['date', 'dateTime', 'time',]) !== false;
    }

    public static
    function getFieldRuleType($typeText)
    {

        $ruleType = '';
        list($type, $options) = self::fieldTypeOptions($typeText);

        if (!str_contains($options, ['hidden', 'guarded'])) {
            if (GeneratorsServiceProvider::isFieldBoolean($typeText)) $ruleType = 'boolean';
            elseif (GeneratorsServiceProvider::isFieldNumeric($typeText)) $ruleType = 'numeric';
            else {
                $ruleTypes = ['date' => 'date'];

                if (array_key_exists($type, $ruleTypes)) {
                    $ruleType = $ruleTypes[$type];
                }
            }
        }

        return $ruleType;
    }

    public static
    function isFieldIntegral($typeText)
    {
        //$table->bigInteger('votes')
        //$table->integer('votes')
        //$table->mediumInteger('numbers')
        //$table->smallInteger('votes')
        //$table->tinyInteger('numbers')
        list($type, $options) = self::fieldTypeOptions($typeText);
        return array_search($type, ['bigInteger', 'integer', 'mediumInteger', 'smallInteger', 'tinyInteger', 'bitset']) !== false;
    }

    public static
    function isFieldNumeric($typeText)
    {
        //$table->decimal('amount', 5, 2)
        //$table->double('column', 15, 8)
        //$table->float('amount')
        list($type, $options) = self::fieldTypeOptions($typeText);
        return str_starts_with($type, ['decimal', 'double', 'float',]) !== false || self::isFieldIntegral($typeText);
    }

    /**
     * @param $prefix
     * @param $package
     * @param $template
     *
     * @return mixed
     */
    public static
    function replacePrefixTemplate($prefix, $package, $template)
    {
        if (!$prefix && !$package) {
            $template = str_replace(['{{prefixdef}}', '{{prefix}}', '{{use}}'], '', $template);
            return $template;
        }
        elseif ($prefix) {
            $template = str_replace([
                '{{prefixdef}}',
                '{{prefix}}',
                '{{use}}'
            ], [
                '$prefix = \'' . $prefix . '\'' . "\n\t\t",
                '$prefix.',
                'use ($prefix) ',
            ], $template);
            return $template;
        }
        elseif ($package) {
            $package = explode('/', $package, 2)[1];

            $template = str_replace([
                '{{prefixdef}}',
                '{{prefix}}',
                '{{use}}'
            ], [
                '$prefix = \Config::get(\'' . $package . '::config.table_prefix\', \'\');' . "\n\t\t",
                '$prefix.',
                'use ($prefix) ',
            ], $template);
            return $template;
        }
        return $template;
    }
}
