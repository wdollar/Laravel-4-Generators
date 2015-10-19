<?php

namespace Vsch\Generators\Generators;

use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class ControllerGenerator extends Generator
{
    protected $template;

    protected
    function replaceLines($template, $modelVars)
    {
        $relationModelList = GeneratorsServiceProvider::getRelationsModelVarsList(GeneratorsServiceProvider::splitFields($this->cache->getFields(), true));

        $fields = GeneratorsServiceProvider::splitFields($this->cache->getFields(), SCOPED_EXPLODE_WANT_ID_RECORD | SCOPED_EXPLODE_WANT_TEXT);

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line}}', function ($line, $fieldVar) use ($fields) {
            $fieldText = '';
            foreach ($fields as $field => $type) {
                $fieldText .= str_replace($fieldVar, $field, $line) . "\n";
            }
            if ($fieldText === '') $fieldText = "''";
            return $fieldText;
        });

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line:bool}}', function ($line, $fieldVar) use ($fields) {
            $fieldText = '';
            foreach ($fields as $field => $type) {
                if (preg_match('/\bboolean\b/', $type)) {
                    $fieldText .= str_replace('{{field:line:bool}}', $field, $line) . "\n";
                }
            }
            return $fieldText;
        });

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line:nobool}}', function ($line, $fieldVar) use ($fields) {
            $fieldText = '';
            foreach ($fields as $field => $type) {
                if (!preg_match('/\bboolean\b/', $type)) {
                    $fieldText .= str_replace('{{field:line:nobool}}', $field, $line) . "\n";
                }
            }
            return $fieldText;
        });

        // add only unique lines
        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{relations:line}}', function ($line, $fieldVar) use ($fields, $relationModelList) {
            // we don't need the marker
            $line = str_replace($fieldVar, '', $line);

            $fieldText = '';
            $fieldTexts = [];
            foreach ($fields as $field => $type) {
                // here we override for foreign keys
                if (array_key_exists($field, $relationModelList)) {
                    $modelVars = $relationModelList[$field];

                    // Replace template vars
                    $text = GeneratorsServiceProvider::replaceModelVars($line, $modelVars, '{{relations:', '}}') . "\n";
                    if (array_search($text, $fieldTexts) === false) {
                        $fieldText .= $text;
                        $fieldTexts[] = $text;
                    }
                }
            }

            return $fieldText;
        });

        // add only unique lines
        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{relations:line:with_model}}', function ($line, $fieldVar) use ($fields, $relationModelList, $modelVars) {
            // we don't need the marker
            $line = str_replace($fieldVar, '', $line);

            $fieldText = '';
            $fieldTexts = [];
            if ($modelVars) {
                // add model
                $text = GeneratorsServiceProvider::replaceModelVars($line, $modelVars, '{{relations:', '}}') . "\n";
                if (array_search($text, $fieldTexts) === false) {
                    $fieldText .= $text;
                    $fieldTexts[] = $text;
                }
            }
            foreach ($fields as $field => $type) {
                // here we override for foreign keys
                if (array_key_exists($field, $relationModelList)) {
                    $relationModelVars = $relationModelList[$field];

                    // Replace template vars
                    $text = GeneratorsServiceProvider::replaceModelVars($line, $relationModelVars, '{{relations:', '}}') . "\n";
                    if (array_search($text, $fieldTexts) === false) {
                        $fieldText .= $text;
                        $fieldTexts[] = $text;
                    }
                }
            }

            return $fieldText;
        });

        if (strpos($this->template, '{{relations') !== false) {
            $relations = '';
            $foreignModel = '';
            $foreignModels = [];
            foreach ($fields as $field => $type) {
                if (array_key_exists($field, $relationModelList)) {
                    $relationModelVars= $relationModelList[$field];
                    if (array_search($relationModelVars['camelModels'], $foreignModels) === false) {
                        $foreignField = strip_suffix($field, "_id");
                        $foreignModels[] = $relationModelVars['camelModels'];
                        $foreignFieldList = "'${relationModelVars['name']}', '${relationModelVars['id']}'";

                        $relations .= <<<PHP
    /**
     * @return array ${relationModelVars['CamelModel']}
     */
    public
    function ${relationModelVars['camelModels']}List($${modelVars['camelModel']} = null)
    {
        // fill the foreign list for ${relationModelVars['CamelModel']}
        if ($${modelVars['camelModel']} !== null) {
            $${relationModelVars['camelModels']} = !$${modelVars['camelModel']}->$foreignField ? [] : [ $${modelVars['camelModel']}->$foreignField->${relationModelVars['id']} => $${modelVars['camelModel']}->$foreignField->${relationModelVars['name']} ];
        } else {
            $${relationModelVars['camelModels']} = ${relationModelVars['CamelModel']}::query()->get([{$foreignFieldList}])->lists({$foreignFieldList})->all();
        }
        return $${relationModelVars['camelModels']};
    }

PHP;
                    }
                }
            }

            $template = str_replace('{{relations}}', $relations, $template);
            if ($relationModelList) {
                $relationsVars = [];
                foreach ($relationModelList as $fieldName => $relationModelVars) {
                    foreach ($relationModelVars as $relationModel => $relationModelVar) {
                        // append
                        if (array_key_exists($relationModel, $relationsVars)) {
                            $relationsVars[$relationModel] .= ", '$relationModelVar' => $$relationModelVar";
                        } else {
                            $relationsVars[$relationModel] = "'$relationModelVar' => $$relationModelVar";
                        }
                    }
                }

                $template = GeneratorsServiceProvider::replaceModelVars($template, $relationsVars, '{{relations:compact:', '}}');
            }
        }

        if (strpos($this->template, '{{auto}}') !== false) {
            $relations = '';
            foreach ($fields as $field => $type) {
                $options = scopedExplode(':', ['(' => ')', '[' => ']', '{' => '}'], $type, null);
                foreach ($options as $option) {
                    if (str_starts_with($option, 'auto')) {
                        $auto = substr($option, 5, -1);
                        $relations .= <<<PHP
        \$input['$field'] = $auto;

PHP;
                    }
                }
            }
            $template = str_replace('{{auto}}', $relations, $template);
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
        $resource = strtolower(Pluralizer::plural(str_ireplace('Controller', '', $className)));

        if ($this->needsScaffolding($template)) {
            $this->template = $this->getScaffoldedController($template, $className);
        }

        $template = str_replace('{{className}}', $className, $this->template);
        $template = str_replace('{{collection}}', $resource, $template);
        $template = $this->replaceLines($template, []);

        return $this->replaceStandardParams($template);
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
        $template = $this->replaceLines($template, $modelVars);

        return $template;
    }
}
