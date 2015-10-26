<?php namespace Vsch\Generators\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Vsch\Generators\GeneratorsServiceProvider;

abstract
class BaseGeneratorCommand extends Command
{
    const PATH_CODE = 'code';
    const PATH_COMMANDS = 'commands';
    const PATH_CONFIG = 'config';
    const PATH_CONTROLLERS = 'controllers';
    const PATH_LANG = 'lang';
    const PATH_MIGRATIONS = 'migrations';
    const PATH_MODELS = 'models';
    const PATH_PUBLIC = 'public';
    const PATH_ROUTES = 'routes';
    const PATH_SEEDS = 'seeds';
    const PATH_TEMPLATES = 'templates';
    const PATH_TESTS = 'tests';
    const PATH_VIEWS = 'views';

    abstract protected
    function getPath();

    /**
     * Model generator instance.
     *
     * @var \Vsch\Generators\Generators\Generator
     */
    protected $generator;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public
    function fire()
    {
        $path = $this->getPath();
        $template = $this->option('template');
        $this->generator->setOptions($this->option());

        $this->printResult($this->generator->make($path, $template, $finalPath), $path, $finalPath);
    }

    protected
    function commonOptions($args, $prefix = null)
    {
        if ($prefix === null) $prefix = '--';

        foreach (self::getOptions() as $option) {
            $args[$prefix . $option[0]] = $this->option($option[0]);
        }
        return $args;
    }

    protected static
    function assocOptions(array $options)
    {
        $assoc_options = [];
        foreach ($options as $option) {
            $assoc_options[$option[0]] = $option;
        }

        return $assoc_options;
    }

    protected
    function mergeOptions($options)
    {
        // combine caller's options with ours
        $assoc_options = self::assocOptions($options);

        foreach (self::getOptions() as $option) {
            if (!array_key_exists($option[0], $assoc_options)) {
                $options[] = $option;
            }
        }

        return $options;
    }

    /**
     * Provide user feedback, based on success or not.
     *
     * @param  boolean $successful
     * @param  string  $path
     *
     * @return void
     */
    protected
    function printResult($successful, $path, $finalPath)
    {
        if ($successful) {
            if ($path !== $finalPath && ends_with($finalPath, ".new")) {
                $this->warn("File {$path} exists. Created {$finalPath} instead.");
            }
            else {
                $this->info("Created {$finalPath}");
            }
            return;
        }

        $this->error("Could not create file, instead created {$path}.new");
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @param      $srcType         String  name of the directory type
     *                              code            - directory for code (app or workbench)
     *                              commands
     *                              config
     *                              controllers
     *                              lang
     *                              migrations
     *                              models
     *                              public
     *                              routes          - directory for the routes.php file
     *                              seeds
     *                              templates
     *                              tests
     *                              views
     *
     *                              The returned path will be adjusted for bench option to map the directory to the right location
     *                              in the workbench/vendor/package subdirectory based on laravel version
     *
     * @param null $suffix
     *
     * @return string
     */
    protected
    function getSrcPath($srcType, $suffix = null)
    {
        if ($this->option('path')) {
            $srcPath = $this->option('path');
        }
        else {
            $srcPath = GeneratorsServiceProvider::getSrcPath($srcType, $this->option('bench'));
        }

        return $suffix ? end_with($srcPath, '/') . $suffix : $srcPath;
    }

    protected
    function getOptions()
    {
        return array(
            array('bench', null, InputOption::VALUE_OPTIONAL, 'workbench package name for which to generate controller', ''),
            array('prefix', null, InputOption::VALUE_OPTIONAL, 'table prefix for migrations', ''),
            array('overwrite', null, InputOption::VALUE_NONE, 'overwrite existing files instead of creating ones with .new extension'),
        );
    }

}
