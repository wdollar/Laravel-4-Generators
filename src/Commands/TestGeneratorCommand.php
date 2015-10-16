<?php namespace Vsch\Generators\Commands;

use Vsch\Generators\Generators\TestGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Vsch\Generators\GeneratorsServiceProvider;

class TestGeneratorCommand extends BaseGeneratorCommand {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'generate:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a PHPUnit test class.';

    /**
     * Create a new command instance.
     *
     * @param TestGenerator $generator
     */
    public function __construct(TestGenerator $generator)
    {
        parent::__construct();

        $this->generator = $generator;
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected function getPath()
    {
       return parent::getSrcPath(self::PATH_TESTS, studly_case($this->argument('name')) . '.php');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('name', InputArgument::REQUIRED, 'Name of the test to generate.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return $this->mergeOptions(array(
           array('path', null, InputOption::VALUE_OPTIONAL, 'Path to tests directory.', ''),
           array('template', null, InputOption::VALUE_OPTIONAL, 'Path to template.', GeneratorsServiceProvider::getTemplatePath('test.txt')),
        ));
    }

}
