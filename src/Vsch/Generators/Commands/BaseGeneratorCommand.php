<?php namespace Vsch\Generators\Commands;

use Illuminate\Support\Pluralizer;
use Vsch\Generators\Generators\ViewGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class BaseGeneratorCommand extends Command
{

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

        $this->printResult($this->generator->make($path, $template), $path);
    }

    protected
    function commonOptions($args, $prefix = null)
    {
        if ($prefix === null) $prefix = '--';

        foreach (self::getOptions() as $option)
        {
            $args[$prefix.$option[0]] = $this->option($option[0]);
        }
        return $args;
    }

    protected static
    function assocOptions(array $options)
    {
        $assoc_options = [];
        foreach ($options as $option)
        {
            $assoc_options[$option[0]] = $option;
        }

        return $assoc_options;
    }

    protected
    function mergeOptions($options)
    {
        // combine caller's options with ours
        $assoc_options = self::assocOptions($options);

        foreach (self::getOptions() as $option)
        {
            if (!array_key_exists($option[0], $assoc_options))
            {
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
    function printResult($successful, $path)
    {
        if ($successful)
        {
            $this->info("Created {$path}");
            return;
        }

        $this->error("Could not create file, instead created {$path}.new");
    }

    protected static
    function benchPath($package)
    {
        return base_path() . '/workbench/' . $package . '/src';
    }

    protected static
    function benchCodePath($package)
    {
        $parts = explode('/', $package, 2);
        return self::benchPath($package) . '/' . ucfirst($parts[0]) . '/' . str_replace(' ', '', ucwords(str_replace('-', ' ', $parts[1])));
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected
    function getSrcPath($subdir = null, $suffix = null, $benchSubDir = null)
    {
        if ($benchSubDir === null) $benchSubDir = $subdir;
        return ($this->option('path') ?: (($this->option('bench') ? self::benchPath($this->option('bench')) . $benchSubDir : app_path() . $subdir))) . ($suffix ? $suffix : '');
    }

    /**
     * Get the path to the file that should be generated, if it is a bench='' option run then use extended package name (name/package-name/src/Name/PackageName) for the path
     *
     * this is needed by model generator
     *
     *
     * @return string
     */
    protected
    function getCodePath($subdir = null, $suffix = null, $benchSubDir = null)
    {
        if ($benchSubDir === null) $benchSubDir = $subdir;
        return ($this->option('path') ?: (($this->option('bench') ? self::benchCodePath($this->option('bench')) . $benchSubDir : app_path() . $subdir))) . ($suffix ? $suffix : '');
    }

    protected
    function getOptions()
    {
        return array(
            array('bench', null, InputOption::VALUE_OPTIONAL, 'workbench package name for which to generate controller', ''),
        );
    }
}
