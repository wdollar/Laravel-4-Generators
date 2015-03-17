<?php

namespace Vsch\Generators\Generators;

use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class ControllerGenerator extends Generator
{
    protected $template;

    protected
    function replaceLines($template)
    {
        $fields = $this->cache->getFields();

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line}}', function ($line, $fieldVar) use ($fields)
        {
            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                $fieldText .= str_replace($fieldVar, $field, $line) . "\n";
            }
            if ($fieldText === '') $fieldText = "''";
            return $fieldText;
        });

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line:bool}}', function ($line, $fieldVar) use ($fields)
        {
            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                if (preg_match('/\bboolean\b/', $type))
                {
                    $fieldText .= str_replace('{{field:line:bool}}', $field, $line) . "\n";
                }
            }
            return $fieldText;
        });

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line:nobool}}', function ($line, $fieldVar) use ($fields)
        {
            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                if (!preg_match('/\bboolean\b/', $type))
                {
                    $fieldText .= str_replace('{{field:line:nobool}}', $field, $line) . "\n";
                }
            }
            return $fieldText;
        });

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{relations:line}}', function ($line, $fieldVar) use ($fields)
        {
            // we don't need the marker
            $line = str_replace($fieldVar, '', $line);

            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                // here we override for foreign keys
                if (substr($field, strlen($field) - 3) === '_id')
                {
                    // assume foreign key
                    $foreignModel = substr($field, 0, strlen($field) - 3);
                    $modelVars = GeneratorsServiceProvider::getModelVars($foreignModel);

                    // Replace template vars
                    $fieldText .= GeneratorsServiceProvider::replaceModelVars($line, $modelVars, '{{relations:', '}}') . "\n";
                }
            }

            return $fieldText;
        });

        $relationModelList = [];
        if (strpos($this->template, '{{relations') !== false)
        {
            $relations = '';
            $foreignModel = '';
            foreach ($fields as $field => $type)
            {
                if (substr($field, strlen($field) - 3) === '_id')
                {
                    // assume foreign key
                    // add foreign keys
                    $foreignModel = substr($field, 0, strlen($field) - 3);
                    $modelVars = GeneratorsServiceProvider::getModelVars($foreignModel);

                    $relationModelList[] = $foreignModel;
                    $relations .= <<<PHP
    /**
     * @return array ${modelVars['Model']}
     */
    public
    function ${modelVars['camelModels']}List(\$id)
    {
        $${modelVars['camelModels']} = [ ];
        // fill the foreign list for ${modelVars['camelModel']} \$id
        return $${modelVars['camelModels']};
    }

PHP;
                }
            }

            $template = str_replace('{{relations}}', $relations, $template);

            if ($foreignModel)
            {
                $relationsVars = [];
                foreach ($relationModelList as $relationModel)
                {
                    $relationModelVars = GeneratorsServiceProvider::getModelVars($relationModel);
                    foreach ($relationModelVars as $relationModel => $relationModelVar)
                    {
                        // append
                        if (array_key_exists($relationModel, $relationsVars))
                        {
                            $relationsVars[$relationModel] .= ", '$relationModelVar' => $$relationModelVar";
                        }
                        else
                        {
                            $relationsVars[$relationModel] = "'$relationModelVar' => $$relationModelVar";
                        }
                    }
                }

                $template = GeneratorsServiceProvider::replaceModelVars($template, $relationsVars, '{{relations:compact:', '}}');
            }
        }

        return $template;
    }

    /**
     * Fetch the compiled template for a controller
     *
     * @param  string $template Path to template
     * @param  string $name
     *
     * @return string Compiled template
     */
    protected
    function getTemplate($template, $className)
    {
        $this->template = $this->file->get($template);
        $resource = strtolower(Pluralizer::plural(
            str_ireplace('Controller', '', $className)
        ));

        if ($this->needsScaffolding($template))
        {
            $this->template = $this->getScaffoldedController($template, $className);
        }

        $template = str_replace('{{className}}', $className, $this->template);
        $template = str_replace('{{collection}}', $resource, $template);
        $template = $this->replaceLines($template);

        return $template;
    }

    /**
     * Get template for a scaffold
     *
     * @param  string $template Path to template
     * @param  string $name
     *
     * @return string
     */
    protected
    function getScaffoldedController($template, $className)
    {
        $template = $this->template;
        $modelVars = GeneratorsServiceProvider::getModelVars($this->cache->getModelName());

        // Replace template vars
        $template = GeneratorsServiceProvider::replaceModelVars($template, $modelVars);
        $template = $this->replaceLines($template);

        return $template;
    }
}
