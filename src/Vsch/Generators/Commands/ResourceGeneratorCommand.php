<?php namespace Vsch\Generators\Commands;

use Vsch\Generators\Generators\ResourceGenerator;
use Vsch\Generators\Cache;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class MissingFieldsException extends \Exception
{
}

class TemplateNameDoesNotExist extends \Exception
{
}

class ResourceGeneratorCommand extends BaseGeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:resource';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a resource.';
    /**
     * Model generator instance.
     *
     * @var \Vsch\Generators\Generators\ResourceGenerator
     */
    protected $generator;
    /**
     * File cache.
     *
     * @var Cache
     */
    protected $cache;
    protected $model;
    protected $fields;
    protected $templateDirs;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public
    function __construct(ResourceGenerator $generator, Cache $cache)
    {
        parent::__construct();

        $this->generator = $generator;
        $this->cache = $cache;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public
    function fire()
    {
        // Scaffolding should always begin with the singular
        // form of the now.
        $this->model = Pluralizer::singular($this->argument('name'));

        // common error for field types
        $fields = trim($this->option('fields'));
        $fields = GeneratorsServiceProvider::mapFieldTypes($fields);

        $this->fields = $fields;

        $templateDir = str_finish($this->option('template-dir'), "/");

        $defaultDirs = $this->getDefaultTemplateSubDirs();

        if ($this->fields === null)
        {
            throw new MissingFieldsException('You must specify the fields option.');
        }

        if (!is_dir(GeneratorsServiceProvider::getTemplatePath($this->templateDirs, '/')))
        {
            throw new TemplateNameDoesNotExist('template-name ' . $this->templateDirs . ' is not a sub-directory or templates/.');
        }

        $templateDir = str_finish($templateDir, "/");
        $isDefault = false;
        foreach ($defaultDirs as &$defaultDir)
        {
            $defaultDir = str_finish($defaultDir, "/");
            if ($templateDir === $defaultDir) $isDefault = true;
        }
        $this->templateDirs = $isDefault ? [] : [$templateDir];
        $this->templateDirs = array_merge($this->templateDirs, $defaultDirs);

        // We're going to need access to these values
        // within future commands. I'll save them
        // to temporary files to allow for that.
        $this->cache->fields($this->fields);
        $this->cache->modelName($this->model);

        $this->generateModel();
        $this->generateController();
        $this->generateViews();
        $this->generateMigration();

        if (!$this->option('bench'))
        {
            $this->generateSeed();
        }

        $this->generateTranslations();

        if (get_called_class() === 'Vsch\\Generators\\Commands\\ScaffoldGeneratorCommand')
        {
            $this->generateTest();
        }

        if (!$this->option('bench'))
        {
            if ($this->generator->updateRoutesFile($this->model, $this->getRouteTemplatePath())) $this->info('Updated ' . app_path() . '/routes.php');
            else $this->info('Did not need to update ' . app_path() . '/routes.php');
        }
        else
        {
            $this->info('Running --bench option, file needs to be manually updated: ' . app_path() . '/routes.php');
        }

        // We're all finished, so we
        // can delete the cache.
        $this->cache->destroyAll();
    }

    /**
     * Get the path to the template for a model.
     *
     * @return string
     */
    protected
    function getModelTemplatePath()
    {
        //return self::getTemplatePath('model.txt');
        return GeneratorsServiceProvider::getTemplatePath($this->templateDirs, 'model.txt');
    }

    /**
     * Get the path to the template for a model.
     *
     * @return string
     */
    protected
    function getTranslationsTemplatePath()
    {
        //return self::getTemplatePath('model.txt');
        return GeneratorsServiceProvider::getTemplatePath($this->templateDirs, 'translations.txt');
    }

    protected
    function getRouteTemplatePath()
    {
        return GeneratorsServiceProvider::getTemplatePath($this->templateDirs, 'route.txt');
    }

    /**
     * Get the path to the template for a controller.
     *
     * @return string
     */
    protected
    function getControllerTemplatePath()
    {
        return GeneratorsServiceProvider::getTemplatePath($this->templateDirs, 'controller.txt');
    }

    /**
     * Get the path to the template for a view.
     *
     * @return string
     */
    protected
    function getViewTemplatePath($view = 'view')
    {
        return GeneratorsServiceProvider::getTemplatePath($this->templateDirs, 'view.txt');
    }

    /**
     * Call generate:model
     *
     * @return void
     */
    protected
    function generateModel()
    {
        // For now, this is just the regular model template
        $this->call('generate:model', parent::commonOptions(array(
            'name' => $this->model,
            '--template' => $this->getModelTemplatePath()
        )));
    }

    /**
     * Call generate:translations
     *
     * @return void
     */
    protected
    function generateTranslations()
    {
        // For now, this is just the regular model template
        $this->call('generate:translations', parent::commonOptions(array(
            'name' => $this->model,
            '--template' => $this->getTranslationsTemplatePath()
        )));
    }

    /**
     * Call generate:controller
     *
     * @return void
     */
    protected
    function generateController()
    {
        $name = Pluralizer::plural($this->model);

        $this->call('generate:controller', parent::commonOptions(array(
            'name' => "{$name}Controller",
            '--template' => $this->getControllerTemplatePath()
        )));
    }

    /**
     * Call generate:test
     *
     * @return void
     */
    protected
    function generateTest()
    {
        if ($this->option('bench'))
        {
            $path = parent::getSrcPath('/../tests');
            if (!file_exists($path)) mkdir($path);
            $path = parent::getSrcPath('/../tests/controllers');
            if (!file_exists($path)) mkdir($path);
        }
        else
        {
            $path = parent::getSrcPath('/tests/controllers');
            if (!file_exists($path)) mkdir($path);
        }

        $this->call('generate:test', parent::commonOptions(array(
            'name' => Pluralizer::plural(strtolower(substr($this->model, 0, 1)) . substr($this->model, 1)) . 'Test',
            '--template' => $this->getTestTemplatePath(),
            '--path' => $path
        )));
    }

    /**
     * Call generate:views
     *
     * @return void
     */
    protected
    function generateViews()
    {
        $viewsDir = parent::getSrcPath('/views');
        $container = $viewsDir . '/' . Pluralizer::plural($this->model);
        $layouts = $viewsDir . '/layouts';
        $adminOnlyView = false;

        if (file_exists($this->getViewTemplatePath('admin')))
        {
            // generate only one view for create, edit, show called admin
            $views = array('index', 'admin');
            $adminOnlyView = true;
        }
        else
        {
            $views = array('index', 'show', 'create', 'edit');
        }

        $this->generator->folders(array($container));

        // If generating a scaffold, we also need views/layouts/scaffold
        if (!$adminOnlyView && get_called_class() === 'Vsch\\Generators\\Commands\\ScaffoldGeneratorCommand')
        {
            $views[] = 'scaffold';
            $this->generator->folders($layouts);
        }

        // Let's filter through all of our needed views
        // and create each one.
        foreach ($views as $view)
        {
            $path = $view === 'scaffold' ? $layouts : $container;
            $this->generateView($view, $path);
        }
    }

    /**
     * Generate a view
     *
     * @param  string $view
     * @param  string $path
     *
     * @return void
     */
    protected
    function generateView($view, $path)
    {
        $this->call('generate:view', parent::commonOptions(array(
            'name' => $view,
            '--path' => $path,
            '--template' => $this->getViewTemplatePath($view)
        )));
    }

    /**
     * Call generate:migration
     *
     * @return void
     */
    protected
    function generateMigration()
    {
        $name = 'create_' . snake_case(Pluralizer::plural($this->model)) . '_table';

        $this->call('generate:migration', parent::commonOptions(array(
            'name' => $name,
            '--fields' => $this->fields,
        )));
    }

    protected
    function generateSeed()
    {
        $this->call('generate:seed', parent::commonOptions(array(
            'name' => Pluralizer::plural(strtolower(substr($this->model, 0, 1)) . substr($this->model, 1)),
        )));
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected
    function getArguments()
    {
        return array(
            array('name', InputArgument::REQUIRED, 'Name of the desired resource.'),
        );
    }

    /**
     * Get the default template subdir.
     *
     * @return array
     */
    protected
    function getDefaultTemplateSubDirs()
    {
        // just use templates
        return [''];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected
    function getOptions()
    {
        return $this->mergeOptions(array(
            array(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'The path to the app directory',
                ''
            ),
            array('fields', null, InputOption::VALUE_OPTIONAL, 'Table fields', null),
            array(
                'template-dir',
                null,
                InputOption::VALUE_OPTIONAL,
                'What template sub-directory to use? [|scaffold|any-dir]',
                $this->getDefaultTemplateSubDirs()[0]
            ),
        ));
    }
}
