<?php

namespace Vsch\Generators\Generators;

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

        if ($this->needsScaffolding($template)) {
            $this->template = $this->getScaffoldedModel($className);
        }

        $template = str_replace('{{className}}', $className, $this->template);
        return $this->replaceStandardParams($template);
    }

    /**
     * Get template for a scaffold
     *
     * @param $className
     *
     * @return string
     */
    protected
    function getScaffoldedModel($className)
    {
        if (!$fields = $this->cache->getFields()) {
            return str_replace('{{rules}}', '', $this->template);
        }

        $template = $this->template;

        $template = str_replace('{{__construct}}', <<<'PHP'
    protected $table = '{{snake_models}}';

    public function __construct($attributes = [])
    {
        {{prefixdef}}$this->table = {{prefix}}'{{snake_models}}';
        parent::__construct($attributes);
    }

PHP
            , $this->template);

        $prefix = $this->options('prefix');
        $package = $this->options('bench');
        $template = GeneratorsServiceProvider::replacePrefixTemplate($prefix, $package, $template);

        $fields = GeneratorsServiceProvider::splitFields(implode(',', $fields), true);
        $modelVars = GeneratorsServiceProvider::getModelVars($this->cache->getModelName());

        // Replace template vars
        $template = GeneratorsServiceProvider::replaceModelVars($template, $modelVars);

        $relationModelList = GeneratorsServiceProvider::getRelationsModelVarsList($fields);

        if (strpos($template, '{{relations') !== false) {
            $relations = '';
            foreach ($fields as $field) {
                // add foreign keys
                $name = $field->name;
                $relFuncName = trim_suffix($name, '_id');

                if (array_key_exists($name, $relationModelList)) {
                    $foreignModelVars = $relationModelList[$name];
                    $relations .= <<<PHP
    /**
     * @return \\Illuminate\\Database\\Eloquent\\Relations\\Relation
     */
    public
    function $relFuncName()
    {
        return \$this->belongsTo('{{app_namespace}}\\${foreignModelVars['CamelModel']}', '$name', '${foreignModelVars['id']}');
    }

PHP;
                }
            }

            $template = str_replace('{{relations}}', $relations, $template);

            if ($relationModelList) {
                $relationsVars = [];
                foreach ($relationModelList as $name => $relationModelVars) {
                    foreach ($relationModelVars as $relationModel => $relationModelVar) {
                        // append
                        if (array_key_exists($relationModel, $relationsVars)) {
                            $relationsVars[$relationModel] .= ", '$relationModelVar'";
                        } else {
                            $relationsVars[$relationModel] = "'$relationModelVar'";
                        }
                    }
                }

                $template = GeneratorsServiceProvider::replaceModelVars($template, $relationsVars, '{{relations:', '}}');
                $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{relation:line}}', function ($line, $fieldVar) use ($fields, $relationModelList) {
                    $fieldText = '';
                    $line = str_replace($fieldVar, '', $line);
                    foreach ($fields as $field) {
                        // add foreign keys
                        $name = $field->name;
                        if (array_key_exists($name, $relationModelList)) {
                            $relationsVars = $relationModelList[$name];
                            $fieldText .= GeneratorsServiceProvider::replaceModelVars($line, $relationsVars, '{{relation:', '}}') . "\n";
                        }
                    }
                    return $fieldText;
                });
            } else {
                $emptyVars = $modelVars;

                array_walk($emptyVars, function (&$val) {
                    $val = '';
                });

                $template = GeneratorsServiceProvider::replaceModelVars($template, $emptyVars, '{{relations:', '}}');
                $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{relation:line}}', function ($line, $fieldVar) {
                    return '';
                });
            }
        }

        if (strpos($template, '{{field:unique}}') !== false) {
            $uniqueField = '';
            $keyindices = [0]; // first element is last used index number, keys are _i where i is passed from the parameters, or auto generated, _i => _n_f where n is from params and f index of the field in the fields list

            $fieldIndex = 0;
            foreach ($fields as $field) {
                $fieldIndex++;
                foreach ($field->options as $option) {
                    if (($isKey = strpos($option, 'keyindex') === 0)) {
                        MigrationGenerator::processIndexOption($keyindices, $option, $field->name, $fieldIndex);
                    }
                }
            }

            // we now have the key indices, we can take the first one
            if (count($keyindices) >= 1) {
                // skip the auto counter
                array_shift($keyindices);
                $indexValues = array_values($keyindices);
                if ($indexValues) {
                    $anyIndex = $indexValues[0];
                    $uniqueField = "'" . implode("','", array_values($anyIndex)) . "'";
                }
            } else {
                foreach ($fields as $field) {
                    if (hasIt($field->options, 'unique', HASIT_WANT_PREFIX)) {
                        $uniqueField = "'" . $field->name . "'";
                        break;
                    }
                }
            }

            if ($uniqueField === '') $uniqueField = "'id'";

            $template = str_replace('{{field:unique}}', $uniqueField, $template);
        }

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line}}', function ($line, $fieldVar) use ($fields) {
            $fieldText = '';
            foreach ($fields as $field) {
                $fieldText .= str_replace($fieldVar, $field->name, $line) . "\n";
            }
            if ($fieldText === '') $fieldText = "''";
            return $fieldText;
        });

        $template = GeneratorsServiceProvider::replaceTemplateLines($template, '{{field:line:bool}}', function ($line, $fieldVar) use ($fields, $modelVars) {
            $fieldText = '';
            foreach ($fields as $field) {
                if (GeneratorsServiceProvider::isFieldBoolean($field->type)) {
                    $fieldText .= str_replace($fieldVar, $field->name, $line) . "\n";
                }
            }
            return $fieldText;
        });

        $rules = [];
        $guarded = [];
        $hidden = [];
        $notrail = [];
        $notrailonly = [];
        $defaults = [];
        $fieldText = '';

        foreach ($fields as $field) {
            if ($field->name !== 'id') {
                if ($fieldText) $fieldText .= ', ';
                $fieldText .= $field->name . ":" . implode(':', $field->options);
            }

            if (!hasIt($field->options, ['hidden', 'guarded'], HASIT_WANT_PREFIX)) {
                $ruleBits = [];

                if ($rule = hasIt($field->options, 'rule', HASIT_WANT_PREFIX | HASIT_WANT_VALUE)) {
                    $rule = substr($rule, strlen('rule('), -1);
                    $ruleBits[] = $rule;
                }

                if ($default = hasIt($field->options, 'default', HASIT_WANT_PREFIX | HASIT_WANT_VALUE)) {
                    $default = substr($default, strlen('default('), -1);
                    $defaults[$field->name] = $default;
                } elseif (hasIt($field->options, 'nullable', HASIT_WANT_PREFIX)) {
                    $defaults[$field->name] = null;
                }

                if (array_search('sometimes', $ruleBits) === false && !array_key_exists($field->name, $defaults) && !GeneratorsServiceProvider::isFieldBoolean($field->type) && !hasIt($field->options, [
                        'nullable',
                        'hidden',
                        'guarded'
                    ], HASIT_WANT_PREFIX)
                ) $ruleBits[] = 'required';

                if ($field->name === 'email') array_unshift($ruleBits, 'email');
                $ruleType = GeneratorsServiceProvider::getFieldRuleType($field->type);
                if ($ruleType) array_unshift($ruleBits, $ruleType);

                if (hasIt($field->options, ['unique'], HASIT_WANT_PREFIX)) {
                    $ruleBits[] = "unique:{$modelVars['snake_models']},$field->name,{{id}}";
                }

                // here we override for foreign keys
                if (array_key_exists($field->name, $relationModelList)) {
                    $relationsVars = $relationModelList[$field->name];
                    $table_name = $relationsVars['snake_models'];
                    $ruleBits[] = "exists:$table_name,id";
                }

                $rules[$field->name] = "'{$field->name}' => '" . implode('|', $ruleBits) . "'";
            }

            if (hasIt($field->options, 'notrail', HASIT_WANT_PREFIX)) $notrail[] = "'{$field->name}'";
            if (hasIt($field->options, 'hidden', HASIT_WANT_PREFIX)) $hidden[] = "'{$field->name}'";
            if (hasIt($field->options, 'guarded', HASIT_WANT_PREFIX)) $guarded[] = "'{$field->name}'";
            if (hasIt($field->options, 'notrailonly', HASIT_WANT_PREFIX)) $notrailonly[] = "'{$field->name}'";
        }

        $defaultValues = [];
        foreach ($defaults as $field => $value) {
            if ($value === null || strtolower($value) === 'null') {
                $value = 'null';
            } elseif (!(GeneratorsServiceProvider::isFieldNumeric($fields[$field]->type)
                || GeneratorsServiceProvider::isFieldBoolean($fields[$field]->type))
            ) {
                $value = "'$value'";
            }
            $defaultValues[] = "'$field' => $value";
        }

        $template = str_replace('{{fields}}', $fieldText, $template);
        $template = str_replace('{{rules}}', $this->implodeOneLineExpansion($rules), $template);
        $template = str_replace('{{hidden}}', $this->implodeOneLineExpansion($hidden), $template);
        $template = str_replace('{{guarded}}', $this->implodeOneLineExpansion($guarded), $template);
        $template = str_replace('{{notrail}}', $this->implodeOneLineExpansion($notrail), $template);
        $template = str_replace('{{notrailonly}}', $this->implodeOneLineExpansion($notrailonly), $template);
        $template = str_replace('{{defaults}}', $this->implodeOneLineExpansion($defaultValues), $template);

        return $template;
    }

    /**
     * @param $rules
     *
     * @return string
     */
    protected
    function implodeOneLineExpansion($rules)
    {
        return empty($rules) ? '' : PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $rules) . PHP_EOL . "\t";
    }
}
