<?php

namespace Vsch\Generators\Generators;

use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class TranslationsGenerator extends Generator
{
    protected $template;

    /**
     * Fetch the compiled template for a model
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

        // Replace template vars
        $modelVars = GeneratorsServiceProvider::getModelVars($this->cache->getModelName());
        $this->template = GeneratorsServiceProvider::replaceModelVars($this->template, $modelVars);

        $fields = $this->cache->getFields() ?: [];

        $this->template = GeneratorsServiceProvider::replaceTemplateLines($this->template, '{{translations:line}}', function ($line, $fieldVar) use ($fields)
        {
            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                if (substr($field, -3) == '_id')
                {
                    // add the foreign model translation
                    $foreignName = strtolower(substr($field, 0, -3));
                    $foreignNameText = ucwords(str_replace('_', ' ', $foreignName));
                    $fieldText .= str_replace($fieldVar, "'$foreignName' => '$foreignNameText',", $line) . "\n";
                }
                $fieldNameTrans =ucwords(str_replace('_', ' ', $field));
                $fieldText .= str_replace($fieldVar, "'$field' => '$fieldNameTrans',", $line) . "\n";
            }
            return $fieldText;
        });

        return $this->template;
    }
}
