<?php

namespace Vsch\Generators\Generators;

use Illuminate\Support\Pluralizer;
use Vsch\Generators\GeneratorsServiceProvider;

class ModelGenerator extends Generator
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

        if ($this->needsScaffolding($template))
        {
            $this->template = $this->getScaffoldedModel($className);
        }

        return str_replace('{{className}}', $className, $this->template);
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
    function getScaffoldedModel($className)
    {
        // TODO: needs to pull unique from field definition into rules
        if (!$fields = $this->cache->getFields())
        {
            return str_replace('{{rules}}', '', $this->template);
        }

        $template = $this->template;
        $modelVars = GeneratorsServiceProvider::getModelVars($this->cache->getModelName());

        // Replace template vars
        $this->template = GeneratorsServiceProvider::replaceModelVars($this->template, $modelVars);

        $relationModelList = [];
        if (strpos($this->template, '{{relations') !== false)
        {
            $relations = '';
            $fname = '';
            foreach ($fields as $field => $type)
            {
                // add foreign keys
                $name = $field;
                if (substr($name, -3) === '_id')
                {
                    // assume foreign key
                    $fname = substr($name, 0, -3); // post
                    $Fname = ucwords($fname);   // Post

                    $relationModelList[] = $fname;
                    $relations .= <<<PHP
    /**
     * @return \\Illuminate\\Database\\Eloquent\\Relations\\Relation
     */
    public
    function $fname()
    {
        return \$this->belongsTo('$Fname', '$field', 'id');
    }

PHP;
                }
            }

            $this->template = str_replace('{{relations}}', $relations, $this->template);

            if ($fname)
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
                            $relationsVars[$relationModel] .= ", '$relationModelVar'";
                        }
                        else
                        {
                            $relationsVars[$relationModel] = "'$relationModelVar'";
                        }
                    }
                }

                $this->template = GeneratorsServiceProvider::replaceModelVars($this->template, $relationsVars, '{{relations:', '}}');
            }
        }

        if (strpos($this->template, '{{field:unique}}') !== false)
        {
            $uniqueField = '';
            foreach ($fields as $field => $type)
            {
                if (strpos($type, 'unique') !== false)
                {
                    $uniqueField = $field;
                    break;
                }
            }
            if ($uniqueField === '') $uniqueField = 'id';

            $this->template = str_replace('{{field:unique}}', $uniqueField, $this->template);
        }

        $this->template = GeneratorsServiceProvider::replaceTemplateLines($this->template, '{{field:line}}', function ($line, $fieldVar) use ($fields)
        {
            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                $fieldText .= str_replace($fieldVar, $field, $line) . "\n";
            }
            if ($fieldText === '') $fieldText = "''";
            return $fieldText;
        });

        $this->template = GeneratorsServiceProvider::replaceTemplateLines($this->template, '{{field:line:bool}}', function ($line, $fieldVar) use ($fields, $modelVars)
        {
            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                if (GeneratorsServiceProvider::isFieldBoolean($type))
                {
                    $fieldText .= str_replace($fieldVar, $field, $line) . "\n";
                }
            }
            return $fieldText;
        });

        $rules = [];
        $guarded = [];
        $hidden = [];
        $notrail = [];
        $notrailonly = [];
        $fieldText = '';

        foreach ($fields as $field => $type)
        {
            if ($field !== 'id')
            {
                if ($fieldText) $fieldText .= ', ';
                $fieldText .= $field . ":" . $type;
            }

            if (!str_contains($type, ['hidden', 'guarded']))
            {
                $ruleBits = [];

                if ($field === 'email') array_unshift($ruleBits, 'email');
                $ruleType = GeneratorsServiceProvider::getFieldRuleType($type);
                if ($ruleType) array_unshift($ruleBits, $ruleType);

                if (!GeneratorsServiceProvider::isFieldBoolean($type) && !str_contains($type, [
                        'nullable',
                        'hidden',
                        'guarded'
                    ])
                ) $ruleBits[] = 'required';

                if (str_contains($type, ['unique']))
                {
                    $ruleBits[] = "unique:{$modelVars['snake_models']},$field,{{id}}";
                }

                // here we override for foreign keys
                if (substr($field, strlen($field) - 3) === '_id')
                {
                    // assume foreign key
                    $foreignModel = substr($field, 0, strlen($field) - 3);
                    $foreignModels = Pluralizer::plural($foreignModel);   // posts
                    $ruleBits[] = "exists:$foreignModels,id";
                }

                $rules[$field] = "'$field' => '" . implode('|', $ruleBits) . "'";
            }

            if (preg_match('/\bnotrail\b/', $type)) $notrail[] = "'$field'";
            if (preg_match('/\bhidden\b/', $type)) $hidden[] = "'$field'";
            if (preg_match('/\bguarded\b/', $type)) $guarded[] = "'$field'";
            if (preg_match('/\bnotrailonly\b/', $type)) $notrailonly[] = "'$field'";
        }

        $this->template = str_replace('{{fields}}', $fieldText, $this->template);
        $this->template = str_replace('{{rules}}', PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $rules) . PHP_EOL . "\t", $this->template);
        $this->template = str_replace('{{hidden}}', PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $hidden) . PHP_EOL . "\t", $this->template);
        $this->template = str_replace('{{guarded}}', PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $guarded) . PHP_EOL . "\t", $this->template);
        $this->template = str_replace('{{notrail}}', PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $notrail) . PHP_EOL . "\t", $this->template);
        $this->template = str_replace('{{notrailonly}}', PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $notrailonly) . PHP_EOL . "\t", $this->template);

        return $this->template;
    }
}
