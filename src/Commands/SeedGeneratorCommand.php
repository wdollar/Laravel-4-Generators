<?php namespace Vsch\Generators\Commands;

use Vsch\Generators\Generators\SeedGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Vsch\Generators\GeneratorsServiceProvider;

class SeedGeneratorCommand extends BaseGeneratorCommand
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a seed file.';

    /**
     * Model generator instance.
     *
     * @var \Vsch\Generators\Generators\SeedGenerator
     */
    protected $generator;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public
    function __construct(SeedGenerator $generator)
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
        $path = $this->getPath($this->argument('name'));
        $className = basename($path, '.php');
        $template = $this->option('template');

        $this->printResult($this->generator->make($path, $template), $path);

        $databaseSeederPath = $this->getPath() . '/DatabaseSeeder.php';
        if ($this->generator->updateDatabaseSeederRunMethod($databaseSeederPath, $className))
        {
            $this->info('Updated ' . $databaseSeederPath);
        }
        else
        {
            $this->comment('Did not need to update ' . $databaseSeederPath);
        }
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected
    function getPath($name = null)
    {
        return parent::getSrcPath('/database/seeds', ($name ? '/' . ucwords($name) . 'TableSeeder.php' : ''), '/seeds');
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
            array('name', InputArgument::REQUIRED, 'Name of the model to generate.'),
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
            array('path', null, InputOption::VALUE_OPTIONAL, 'Path to the seeds directory.', ''),
            array('template', null, InputOption::VALUE_OPTIONAL, 'Path to template.', GeneratorsServiceProvider::getTemplatePath('seed.txt')),
        ));
    }
}
