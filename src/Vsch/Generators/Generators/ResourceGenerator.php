<?php

namespace Vsch\Generators\Generators;

use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class ResourceGenerator
{

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
    public
    function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Update app/routes.php
     *
     * @param  string $name
     *
     * @return boolean
     */
    public
    function updateRoutesFile($name, $templatePath)
    {
        $model = strtolower($name);  // post
        $models = Pluralizer::plural($model);   // posts
        $Models = ucwords($models);             // Posts
        $Model = Pluralizer::singular($Models); // Post

        $routes = file_get_contents(app_path() . '/routes.php');

        if ($this->file->exists($templatePath))
        {
            $newRoute = file_get_contents($templatePath);
        }
        else
        {
            $newRoute = 'Route::resource(\'{{models}}\', \'{{Models}}Controller\');' . "\n";
        }

        $newRoute = str_replace('{{model}}', $model, $newRoute);
        $newRoute = str_replace('{{models}}', $models, $newRoute);
        $newRoute = str_replace('{{Model}}', $Model, $newRoute);
        $newRoute = str_replace('{{Models}}', $Models, $newRoute);

        if (!str_contains($routes, $newRoute))
        {
            if (!str_contains($routes, GeneratorsServiceProvider::GENERATOR_ROUTE_TAG))
            {
                $this->file->append(app_path() . '/routes.php', "\n$newRoute\n" . GeneratorsServiceProvider::GENERATOR_ROUTE_TAG . "\n\n");
            }
            else
            {
                $routes = str_replace(GeneratorsServiceProvider::GENERATOR_ROUTE_TAG, $newRoute . GeneratorsServiceProvider::GENERATOR_ROUTE_TAG, $routes);
                file_put_contents(app_path() . '/routes.php', $routes);
            }

            return true;
        }
        return false;
    }

    /**
     * Create any number of folders
     *
     * @param  string|array $folders
     *
     * @return void
     */
    public
    function folders($folders)
    {
        foreach ((array)$folders as $folderPath)
        {
            if (!$this->file->exists($folderPath))
            {
                $this->file->makeDirectory($folderPath);
            }
        }
    }
}
