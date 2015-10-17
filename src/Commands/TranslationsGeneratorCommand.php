<?php namespace Vsch\Generators\Commands;

use Illuminate\Support\Pluralizer;
use Vsch\Generators\Generators\TranslationsGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Vsch\Generators\GeneratorsServiceProvider;

class TranslationsGeneratorCommand extends BaseGeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:translations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate translation files for a model.';

    /**
     * Create a new command instance.
     *
     * @param TranslationsGenerator $generator
     */
    public
    function __construct(TranslationsGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public
    function fire()
    {
        $this->generator->setOptions($this->option());
        $template = $this->option('template');

        $locales = getDirs($this->getPath(), true);
        foreach ($locales as $locale)
        {
            if ($locale === 'en/')
            {
                $path = $this->getPath($locale);
                $this->printResult($this->generator->make($path, $template, $finalPath), $path, $finalPath);
            }
        }
    }


    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected
    function getPath($locale = null)
    {
        return parent::getSrcPath(self::PATH_LANG, ($locale ? $locale . GeneratorsServiceProvider::replaceModel($this->argument('name'), "{{dash-models}}") . '.php' : ''));
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
            array('name', InputArgument::REQUIRED, 'Name of the model for which to generate translations.'),
        );
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
                'Path to the language translations directory.',
                ''
            ),
            array(
                'template',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to template.',
                GeneratorsServiceProvider::getTemplatePath('translations.txt')
            )
        ));
    }
}
