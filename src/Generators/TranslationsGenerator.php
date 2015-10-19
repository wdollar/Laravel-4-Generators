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

        $relationModelList = GeneratorsServiceProvider::getRelationsModelVarsList(GeneratorsServiceProvider::splitFields($this->cache->getFields(), true));

        // Replace template vars
        $modelVars = GeneratorsServiceProvider::getModelVars($this->cache->getModelName());
        $this->template = GeneratorsServiceProvider::replaceModelVars($this->template, $modelVars);

        $fields = $this->cache->getFields() ?: [];
        $fields = GeneratorsServiceProvider::splitFields(implode(',', $fields), SCOPED_EXPLODE_WANT_ID_RECORD);

        $this->template = GeneratorsServiceProvider::replaceTemplateLines($this->template, '{{translations:line}}', function ($line, $fieldVar) use ($fields, $relationModelList) {
            $fieldTexts = [];

            foreach ($fields + ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime',] as $field => $type) {
                if (array_key_exists($field, $relationModelList)) {
                    // add the foreign model translation
                    $foreignName = $relationModelList[$field]['dash-model'];
                    $foreignNameText = $relationModelList[$field]['Space Model'];
                    $fieldTexts[] = str_replace($fieldVar, "'$foreignName' => '$foreignNameText',", $line);

                    if (ends_with($field, "_id") && strip_suffix($field, "_id") !== $foreignName) {
                        $foreignName = strip_suffix($field, "_id");
                        $foreignNameText = GeneratorsServiceProvider::getModelVars($foreignName)['Space Model'];
                        $fieldTexts[] = str_replace($fieldVar, "'$foreignName' => '$foreignNameText',", $line);
                    }
                }

                $modelVars = GeneratorsServiceProvider::getModelVars($field);
                $fieldNameTrans = ($field !== Pluralizer::plural($field)) ? $modelVars['Space Model'] : $modelVars['Space Models'];
                $fieldTexts[] = str_replace($fieldVar, "'$field' => '$fieldNameTrans',", $line);
            }

            sort($fieldTexts);
            return implode("\n", $fieldTexts);
        });

        $this->template = $this->replaceStandardParams($this->template);
        return $this->template;
    }

    /**
     * Fetch the compiled template for a model
     *
     * @param  string $template text to replace
     *
     * @return string Compiled template
     */
    public
    function getLangTemplate($template)
    {
        // Replace template vars
        $modelVars = GeneratorsServiceProvider::getModelVars($this->cache->getModelName());
        $template = GeneratorsServiceProvider::replaceModelVars($template, $modelVars);

        $template = $this->replaceStandardParams($template);
        return $template;
    }
}
