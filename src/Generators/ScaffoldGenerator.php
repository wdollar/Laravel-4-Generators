<?php

namespace Vsch\Generators\Generators;

use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class ScaffoldGenerator  {

    /**
     * File system instance
     *
     * @var File
     */
    protected $file;

    /**
     * Constructor
     *
     * @param $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
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
        if ($key !== null)
        {
            return $this->options[$key];
        }

        return $this->options;
    }

    /**
     * Update app/routes.php
     *
     * @param  string $name
     * @return boolean
     */
    public function updateRoutesFile($name, $templatePath)
    {
        return false;
    }

    /**
     * Create any number of folders
     *
     * @param  string|array $folders
     * @return void
     */
    public function folders($folders)
    {
        foreach((array) $folders as $folderPath)
        {
            if (! $this->file->exists($folderPath))
            {
                $this->file->makeDirectory($folderPath);
            }
        }
    }

    /**
     * Get compiled template
     *
     * @param  string $template
     * @param  string $name Name of file
     *
     * @return string
     */
    protected
    function getTemplate($template, $name)
    {
        return '';
    }
}
