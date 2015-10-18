<?php namespace Vsch\Generators\Commands;

use Illuminate\Support\Pluralizer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Vsch\Generators\Cache;
use Vsch\Generators\Generators\ResourceGenerator;
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
     * @param ResourceGenerator $generator
     * @param Cache             $cache
     */
    public
    function __construct(ResourceGenerator $generator, Cache $cache)
    {
        parent::__construct();

        $this->generator = $generator;
        $this->cache = $cache;
    }

    protected function getPath()
    {
        // not used
        return null;
    }

    /**
     * Execute the console command.
     *
     * @throws MissingFieldsException
     * @throws TemplateNameDoesNotExist
     */
    public
    function fire()
    {
        $this->generator->setOptions($this->option());
        // Scaffolding should always begin with the singular
        // form of the now.
        $this->model = Pluralizer::singular($this->argument('name'));

        // common error for field types
        $fields = trim($this->option('fields'));
        $fields = GeneratorsServiceProvider::mapFieldTypes($fields);

        $this->fields = $fields;

        $templateDir = str_finish($this->option('template-dir'), "/");

        $defaultDirs = $this->getDefaultTemplateSubDirs();

        if ($this->fields === null) {
            throw new MissingFieldsException('You must specify the fields option.');
        }

        if (!is_dir(GeneratorsServiceProvider::getTemplatePath($this->templateDirs, '/'))) {
            throw new TemplateNameDoesNotExist('template-name ' . $this->templateDirs . ' is not a sub-directory or templates/.');
        }

        $templateDir = str_finish($templateDir, "/");
        $isDefault = false;
        foreach ($defaultDirs as &$defaultDir) {
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

        if (!$this->option('bench')) {
            $this->generateSeed();
        }

        $this->generateTranslations();
        if (get_called_class() === 'Vsch\\Generators\\Commands\\ScaffoldGeneratorCommand') {
            $this->generateLangScaffold();
        }


        if (get_called_class() === 'Vsch\\Generators\\Commands\\ScaffoldGeneratorCommand') {
            $this->generateTest();
        }

        $routesFile = parent::getSrcPath(self::PATH_ROUTES, 'routes.php');

        if ($routesFile) {
            if ($this->generator->updateRoutesFile($routesFile, $this->model, $this->getRouteTemplatePath())) $this->info('Updated ' . $routesFile);
            else $this->warn('Did not need to update ' . $routesFile);
        } else {
            $this->info(self::PATH_ROUTES . ' dir_map not set to a value in config, routes need to be manually updated');
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

    /**
     * Get the path to the template for a model.
     *
     * @return string
     */
    protected
    function getLangScaffoldTemplatePath()
    {
        return GeneratorsServiceProvider::getTemplatePath($this->templateDirs, 'scaffold/lang');
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
     * Get the path to the template for a controller.
     *
     * @return string
     */
    protected
    function getTestTemplatePath()
    {
        return GeneratorsServiceProvider::getTemplatePath($this->templateDirs, 'controller-test.txt');
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
     * Call generate:translations
     *
     * @return void
     */
    protected
    function generateLangScaffold()
    {
        // For now, this is just the regular model template
        $this->call('generate:translations', parent::commonOptions(array(
            'name' => $this->model,
            '--lang' => $this->getLangScaffoldTemplatePath()
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
        $path = parent::getSrcPath(self::PATH_TESTS, 'controllers');
        if (!file_exists($path)) {
            if (!mkdir($path, 0774, true)) {
                // TODO: error, directory not created
            }
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
        $container = parent::getSrcPath(self::PATH_VIEWS, Pluralizer::plural($this->model));
        $layouts = parent::getSrcPath(self::PATH_VIEWS, 'layouts');
        $adminOnlyView = false;

        if (file_exists($this->getViewTemplatePath('admin'))) {
            // generate only one view for create, edit, show called admin
            $views = array('index', 'admin');
            $adminOnlyView = true;
        } else {
            $views = array('index', 'show', 'create', 'edit');
        }

        $this->generator->folders(array($container));

        // If generating a scaffold, we also need views/layouts/scaffold
        if (!$adminOnlyView && get_called_class() === 'Vsch\\Generators\\Commands\\ScaffoldGeneratorCommand') {
            $views[] = 'scaffold';
            $this->generator->folders($layouts);
        }

        // Let's filter through all of our needed views
        // and create each one.
        foreach ($views as $view) {
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
