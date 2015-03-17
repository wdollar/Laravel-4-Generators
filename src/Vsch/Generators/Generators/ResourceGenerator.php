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
        $modelVars = GeneratorsServiceProvider::getModelVars($name);

        $routes = file_get_contents(app_path() . '/routes.php');

        $newRouteDefault = 'Route::resource(\'{{models}}\', \'{{Models}}Controller\');' . "\n";
        if ($this->file->exists($templatePath))
        {
            $newRoute = file_get_contents($templatePath);
        }
        else
        {
            $newRoute = $newRouteDefault;
        }

        $newRoute = GeneratorsServiceProvider::replaceModelVars($newRoute, $modelVars);
        $newRouteDefault = GeneratorsServiceProvider::replaceModelVars($newRouteDefault, $modelVars);

        $routesNoSpaces = str_replace(['\t', ' '], '', $routes);
        $newRouteNoSpaces = str_replace(['\t', ' '], '', $newRoute);
        $newRouteDefaultNoSpaces = str_replace(['\t', ' '], '', $newRouteDefault);
        if (str_contains($routesNoSpaces, $newRouteDefaultNoSpaces) && !str_contains($routesNoSpaces, $newRouteNoSpaces))
        {
            if (substr($newRoute, -1) === "\n") $newRoute = substr($newRoute, 0, -1);
            $newRoute = '// ' . str_replace("\n", "\n// ", $newRoute) . "\n";
            $newRouteNoSpaces = str_replace(['\t', ' '], '', $newRoute);
        }

        if (!str_contains($routesNoSpaces, $newRouteNoSpaces))
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
