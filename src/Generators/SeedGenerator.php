<?php

namespace Vsch\Generators\Generators;

use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class SeedGenerator extends Generator
{

    protected $template;

    /**
     * Fetch the compiled template for a seed
     *
     * @param  string $template Path to template
     * @param  string $className
     *
     * @return string Compiled template
     */
    protected
    function getTemplate($template, $className)
    {
        $this->template = $this->file->get($template);
        $name = Pluralizer::singular(str_replace('TableSeeder', '', $className));
        $modelVars = GeneratorsServiceProvider::getModelVars($name);

        $this->template = str_replace('{{className}}', $className, $this->template);

        $template = GeneratorsServiceProvider::replaceModelVars($this->template, $modelVars);
        return $this->replaceStandardParams($template);
    }

    /**
     * Updates the DatabaseSeeder file's run method to
     * call this new seed class
     *
     * @return mixed
     */
    public
    function updateDatabaseSeederRunMethod($databaseSeederPath, $className)
    {
        $content = $this->file->get($databaseSeederPath);

        if (!strpos($content, "\$this->call('{$className}');")) {
            $content = preg_replace('/(run\(\).+?)}/us', "$1\t\$this->call('{$className}');\n\t}", $content);
            return $this->file->put($databaseSeederPath, $content);
        }

        return false;
    }
}
