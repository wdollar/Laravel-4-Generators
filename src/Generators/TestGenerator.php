<?php

namespace Vsch\Generators\Generators;

use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class TestGenerator extends Generator {

    /**
     * Fetch the compiled template for a test
     *
     * @param  string $template Path to template
     * @param  string $className
     * @return string Compiled template
     */
    protected function getTemplate($template, $className)
    {
        $template = $this->file->get($template);
        $name = $this->cache->getModelName();
        $modelVars = GeneratorsServiceProvider::getModelVars($name);
        $template = GeneratorsServiceProvider::replaceModelVars($template, $modelVars);
        return $template;
    }

}
