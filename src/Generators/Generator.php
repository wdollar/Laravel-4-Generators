<?php

namespace Vsch\Generators\Generators;

use Illuminate\Filesystem\Filesystem as File;
use Vsch\Generators\Cache;

class RequestedCacheNotFound extends \Exception
{
}

abstract
class Generator
{

    /**
     * File path to generate
     *
     * @var string
     */
    public $path;

    /**
     * File system instance
     *
     * @var File
     */
    protected $file;

    /**
     * Cache
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Constructor
     *
     * @param $file
     */
    public
    function __construct(File $file, Cache $cache = null)
    {
        $this->file = $file;
        $this->cache = $cache;
    }

    /**
     * @var array     options for generator
     */
    protected $options;

    public
    function setOptions(array $options)
    {
        // so that we can access options
        $this->options = $options;
    }

    public
    function options($key = null)
    {
        // so that we can access options
        if ($key !== null) {
            return $this->options[$key];
        }

        return $this->options;
    }

    protected
    function replaceStandardParams($template)
    {
        $template = str_replace("{{app_namespace}}", trim_suffix(\App::getNamespace(), '\\'), $template);
        $template = str_replace("{{eol}}", "\n", $template);
        return $template;
    }

    /**
     * Compile template and generate
     *
     * @param  string $path
     * @param  string $template Path to template
     * @param         $finalPath
     *
     * @return bool
     */
    public
    function make($path, $template, &$finalPath)
    {
        $this->name = basename($path, '.php');
        $this->path = $this->getPath($path);
        $template = $this->getTemplate($template, $this->name);

        // check for migration by same name but new date, I'm tired of deleting these files
        $filename = basename($this->path, '');
        $name = strtolower($this->name) . '.php';
        $fileExists = false;

        if ($filename !== $name && str_ends_with($filename, $name)) {
            // must have a date prefix, we should check if a file by the same name exists and append .new to this one
            if ($handle = opendir($basepath = dirname($path))) {
                while (false !== ($entry = readdir($handle))) {
                    if (fnmatch('*_' . $name, $entry, FNM_PERIOD)) {
                        if ($this->options('overwrite')) {
                            // delete the sucker
                            unlink($basepath . '/' . $entry);
                        }
                        else {
                            $fileExists = true;
                        }
                    }

                    if (!$this->options('overwrite') && fnmatch('*_' . $name . '.new', $entry, FNM_PERIOD)) {
                        // delete the sucker
                        unlink($basepath . '/' . $entry);
                    }
                }
                closedir($handle);
            }
        }

        if ($this->options('overwrite') || (!$fileExists && !$this->file->exists($this->path))) {
            $finalPath = $this->path;
            return $this->file->put($this->path, $template) !== false;
        }
        else {
            // put it as .new, and delete previous .new
            $finalPath = $this->path . ".new";
            return $this->file->put($this->path . ".new", $template);
        }
    }

    /**
     * Get the path to the file
     * that should be generated
     *
     * @param  string $path
     *
     * @return string
     */
    protected
    function getPath($path)
    {
        // By default, we won't do anything, but
        // it can be overridden from a child class
        return $path;
    }

    /**
     * Determines whether the specified template
     * points to the scaffolds directory
     *
     * @param  string $template
     *
     * @return boolean
     */
    protected
    function needsScaffolding($template)
    {
        return str_contains($template, 'scaffold');
    }

    /**
     * Get compiled template
     *
     * @param  string $template
     * @param  string $name Name of file
     *
     * @return string
     */
    abstract protected
    function getTemplate($template, $name);
}
