<?php namespace Vsch\Generators\Commands;

use Vsch\Generators\Generators\ViewGenerator;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class BaseGeneratorCommand extends Command {

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $path = $this->getPath();
        $template = $this->option('template');

        $this->printResult($this->generator->make($path, $template), $path);
    }

    /**
     * Provide user feedback, based on success or not.
     *
     * @param  boolean $successful
     * @param  string $path
     * @return void
     */
    protected function printResult($successful, $path)
    {
        // TODO: figure out why it return a value only in one case???
        if ($successful)
        {
            return $this->info("Created {$path}");
        }

        $this->error("Could not create file, instead created {$path}.new");
    }

    /**
     * Get the path to the file that should be generated.
     *
     * @return string
     */
    protected function getPath()
    {
       return $this->option('path') . '/' . strtolower($this->argument('name')) . '.blade.php';
    }

}
