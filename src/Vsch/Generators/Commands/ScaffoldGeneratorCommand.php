<?php namespace Vsch\Generators\Commands;

use Vsch\Generators\Generators\ResourceGenerator;
use Vsch\Generators\Cache;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class MissingTableFieldsException extends \Exception {}

class ScaffoldGeneratorCommand extends ResourceGeneratorCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:scaffold';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate scaffolding for a resource.';

    /**
     * Get the path to the template for a model.
     *
     * @return string
     */
    protected function getModelTemplatePath()
    {
        return GeneratorsServiceProvider::getTemplatePath('scaffold/model.txt');
    }

    /**
     * Get the path to the template for a controller.
     *
     * @return string
     */
    protected function getControllerTemplatePath()
    {
        return GeneratorsServiceProvider::getTemplatePath('scaffold/controller.txt');
    }


    /**
     * Get the path to the template for a controller.
     *
     * @return string
     */
    protected function getTestTemplatePath()
    {
        return GeneratorsServiceProvider::getTemplatePath('scaffold/controller-test.txt');
    }

    /**
     * Get the path to the template for a view.
     *
     * @return string
     */
    protected function getViewTemplatePath($view = 'view')
    {
        return GeneratorsServiceProvider::getTemplatePath("scaffold/views/{$view}.txt");
    }

}
