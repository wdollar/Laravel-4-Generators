<?php

namespace Vsch\Generators\Generators;

use Vsch\Generators\GeneratorsServiceProvider;

class ViewGenerator extends Generator
{
    protected $template;

    /**
     * Fetch the compiled template for a view
     *
     * @param  string $template Path to template
     * @param  string $name
     *
     * @return string Compiled template
     */
    protected
    function getTemplate($template, $name)
    {
        $this->template = $this->file->get($template);

        if ($this->needsScaffolding($template)) {
            return $this->replaceStandardParams($this->getScaffoldedTemplate($name));
        }

        // Otherwise, just set the file
        // contents to the file name
        return $this->replaceStandardParams($name);
    }

    protected
    function adjustBladeWrap($template)
    {
        if (GeneratorsServiceProvider::LARAVEL_VERSION === '4') {
            // TODO: replace by a function that will search for all via regex and not replace unnecessarily, these repeated replacement can have side-effects
            $adjustedTemplate = str_replace(["{{", "}}", "{!!", "!!}", "{{{--", "--}}}"], ["{{{", "}}}", "{{", "}}", "{{--", "--}}"], $template);
            return $adjustedTemplate;
        }
        return $template;
    }

    /**
     * Get the scaffolded template for a view
     *
     * @param  string $name
     *
     * @return string Compiled template
     */
    protected
    function getScaffoldedTemplate($name)
    {
        $modelVars = GeneratorsServiceProvider::getModelVars($this->cache->getModelName());

        // Replace template vars in view
        $this->template = GeneratorsServiceProvider::replaceModelVars($this->template, $modelVars);

        $useLang = false;
        // Create and Edit views require form elements
        if (str_contains($this->template, '{{formElements}}')) {
            $formElements = $this->makeFormElements($modelVars, false, false, false, false);
            $this->template = str_replace('{{formElements}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:readonly}}')) {
            $formElements = $this->makeFormElements($modelVars, true, false, false);
            $this->template = str_replace('{{formElements:readonly}}', $formElements, $this->template);
        }

        // no booleans
        if (str_contains($this->template, '{{formElements:nobool}}')) {
            $formElements = $this->makeFormElements($modelVars, false, false, true);
            $this->template = str_replace('{{formElements:nobool}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:nobool:readonly}}')) {
            $formElements = $this->makeFormElements($modelVars, true, false, true);
            $this->template = str_replace('{{formElements:nobool:readonly}}', $formElements, $this->template);
        }

        // only booleans
        if (str_contains($this->template, '{{formElements:bool}}')) {
            $formElements = $this->makeFormElements($modelVars, false, true, false);
            $this->template = str_replace('{{formElements:bool}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:bool:readonly}}')) {
            $formElements = $this->makeFormElements($modelVars, true, true, false);
            $this->template = str_replace('{{formElements:bool:readonly}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:op}}')) {
            $formElements = $this->makeFormElements($modelVars, false, false, false, true);
            $this->template = str_replace('{{formElements:op}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:bool:op}}')) {
            $formElements = $this->makeFormElements($modelVars, false, true, false, true);
            $this->template = str_replace('{{formElements:bool:op}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:nobool:op}}')) {
            $formElements = $this->makeFormElements($modelVars, false, false, true, true);
            $this->template = str_replace('{{formElements:nobool:op}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:filters}}')) {
            $formElements = $this->makeFormElements($modelVars, false, false, false, false, true);
            $this->template = str_replace('{{formElements:filters}}', $formElements, $this->template);
        }

        // And finally create the table rows
        if (str_contains($this->template, '{{headings:lang}}')) {
            $useLang = true;
            $this->template = str_replace('{{headings:lang}}', '{{headings}}', $this->template);
        } else {
        }

        list($headings, $fields, $editAndDeleteLinks) = $this->makeTableRows($modelVars, $useLang);
        $this->template = str_replace('{{headings}}', implode(PHP_EOL . "\t\t\t\t", $headings), $this->template);
        $this->template = str_replace('{{fields}}', implode(PHP_EOL . "\t\t\t\t\t", $fields) . PHP_EOL . $editAndDeleteLinks, $this->template);
        $this->template = str_replace('{{fields:nobuttons}}', implode(PHP_EOL . "\t\t\t\t\t", $fields), $this->template);

        return $this->adjustBladeWrap($this->template);
    }

    /**
     * Create the table rows
     *
     * @param $modelVars
     * @param $useLang
     *
     * @return array
     *
     */
    protected
    function makeTableRows($modelVars, $useLang)
    {
        $models = $modelVars['models'];
        $dash_models = $modelVars['dash-models'];
        $camelModel = $modelVars['camelModel'];

        $relationModelList = GeneratorsServiceProvider::getRelationsModelVarsList(GeneratorsServiceProvider::splitFields($this->cache->getFields(), true));
        $fields = GeneratorsServiceProvider::splitFields($this->cache->getFields(), SCOPED_EXPLODE_WANT_ID_RECORD | SCOPED_EXPLODE_WANT_TEXT);
        $fields = GeneratorsServiceProvider::filterFieldHavingOption($fields, 'hidden');

        // First, we build the table headings
        if ($useLang) {
            $headings = array_map(function ($field) use ($dash_models) {
                return '<th>@lang(\'' . $dash_models . '.' . $field . '\')</th>';
            }, array_keys($fields));
        } else {
            $headings = array_map(function ($field) {
                return '<th>' . ucwords($field) . '</th>';
            }, array_keys($fields));
        }

        // And then the rows, themselves
        $fields = array_map(function ($field) use ($camelModel, $fields, $relationModelList) {
            list($type, $options) = GeneratorsServiceProvider::fieldTypeOptions($fields[$field]);
            $nullable = (strpos($options, 'nullable') !== false);

            if (strpos($options, 'hidden') !== false) {
                unset($fields[$field]);
                return null;
            }

            if ($type === 'integer' || $type === 'bigInteger') {
                if (array_key_exists($field, $relationModelList)) {
                    $relFuncName = ends_with($field, '_id') ? substr($field, 0, -3) : $field;
                    $nameCol = $relationModelList[$field]['name'];
                    if ($nullable) {
                        return "<td>{{ is_null(\$$camelModel->$field) ? '' : \$$camelModel->$field . ':' . \$$camelModel->{$relFuncName}->$nameCol }}</td>";
                    } else {
                        return "<td>{{ \$$camelModel->$field . ':' . \$$camelModel->{$relFuncName}->$nameCol }}</td>";
                    }
                }
            }
            return "<td>{{ \$$camelModel->$field }}</td>";
        }, array_keys($fields));

        // Now, we'll add the edit and delete buttons.
        $editAndDelete = <<<EOT
                    <td>
                        {!! Form::open(['style' => 'display: inline-block;', 'method' => 'DELETE', 'route' => ['{$models}.destroy', \${$camelModel}->id, ], ]) !!}
                            {!! formSubmit('Delete', ['class' => 'btn btn-danger', ]) !!}
                        {!! Form::close() !!}
                        {!! link_to_route('{$models}.edit', 'Edit', array(\${$camelModel}->id), ['class' => 'btn btn-info',]) !!}
                    </td>
EOT;

        return [$headings, $fields, $editAndDelete];
    }

    /**
     * Add Laravel methods, as string,
     * for the fields
     *
     * @param string $modelVars
     *
     * @param bool   $disable
     *
     * @param bool   $onlyBoolean
     * @param bool   $noBoolean
     * @param bool   $useOp
     *
     * @return string
     * @internal param $model
     */
    public
    function makeFormElements($modelVars, $disable = false, $onlyBoolean = false, $noBoolean = false, $useOp = false, $filterRows = false)
    {
        $formMethods = [];
        $relationModelList = GeneratorsServiceProvider::getRelationsModelVarsList(GeneratorsServiceProvider::splitFields($this->cache->getFields(), true));
        $fields = GeneratorsServiceProvider::splitFields($this->cache->getFields(), SCOPED_EXPLODE_WANT_ID_TYPE_OPTIONS | SCOPED_EXPLODE_WANT_TEXT);
        $models = $modelVars['models'];
        $model = $modelVars['model'];
        $camelModel = $modelVars['camelModel'];
        $narrowText = " input-narrow";
        $dash_models = $modelVars['dash-models'];

        foreach ($fields as $name => $values) {
            $type = $values['type'];
            $options = $values['options'];
            $nameModelVars = GeneratorsServiceProvider::getModelVars($name);

            if (strpos($options, 'hidden') !== false) continue;
            $nullable = (strpos($options, 'nullable') !== false);
            if (strpos($options, 'guarded') !== false) {
                if ($useOp) {
                    $readonly = true ? "['readonly', " : '[';
                    $readonlyHalf = '[true ? \'readonly\' : ';
                    $readonlyClose = ']';
                    $disabled = true ? "'disabled', " : '';
                } else {
                    $readonly = $disable ? "['readonly', " : '[';
                    $readonlyHalf = "[$disable ? 'readonly' : ";
                    $readonlyClose = ']';
                    $disabled = $disable ? "'disabled', " : '';
                }
            } else {
                if ($useOp) {
                    $readonly = '[isViewOp($op) ? \'readonly\' : \'\',';
                    $readonlyHalf = '[isViewOp($op) ? \'readonly\' : ';
                    $readonlyClose = ']';
                    $disabled = 'isViewOp($op) ? \'disabled\' : \'\',';
                } else {
                    $readonly = $disable ? "['readonly', " : '[';
                    $readonlyHalf = "[$disable ? 'readonly' : ";
                    $readonlyClose = ']';
                    $disabled = $disable ? "'disabled', " : '';
                }
            }

            $limit = null;
            $useShort = false;

            if (str_contains($type, '[')) {
                if (preg_match('/([^\[]+?)\[(\d+)(?:\,\d+)?\]/', $type, $matches)) {
                    $type = $matches[1]; // string
                    $limit = $matches[2]; // 50{,...,}
                }
            }

            if (preg_match('/\btextarea\b/', $options)) {
                // treat it as text with multiple rows
                $type = 'text';
            }

            $foreignTable = '';
            if (preg_match('/\btable\(([^)]+)\)\b/', $options, $matches)) {
                // treat it as text with multiple rows
                $foreignTable = $matches[1];
            }

            if ($type === 'boolean' && $noBoolean) continue;
            if ($type !== 'boolean' && $onlyBoolean) continue;

            $trans_name = $name;
            $labelName = $trans_name;
            $labelGroup = $dash_models;
            $afterElement = '';
            $afterElementFilter = '';
            $wrapRow = true;

            $inputNarrow = (GeneratorsServiceProvider::isFieldNumeric($type) || ($type === 'string' && $limit < 32)) ? $narrowText : '';

            switch ($type) {
                case  'mediumInteger':
                case  'smallInteger':
                case  'tinyInteger':
                    $element = "{!! Form::input('number', '$name', Input::old('$name'), $readonly'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), $readonlyClose) !!}";
                    $elementFilter = "{!! Form::input('number', '$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), ]) !!}";
                    break;

                case 'bigInteger':
                case  'integer':
                    if (array_key_exists($name, $relationModelList)) {
                        // assume foreign key
                        $afterElement = "";

                        $foreignModelVars = $relationModelList[$name];
                        $foreignModels = $foreignModelVars['camelModels'];
                        $foreignmodels = $foreignModelVars['models'];
                        $foreign_model = $foreignModelVars['snake_model'];
                        $foreign_models = $foreignModelVars['snake_models'];
                        $foreign_display = $foreignModelVars['name'];

                        $plainName = ends_with($name, '_id') ? substr($name, 0, -3) : $name;
                        $labelName = $plainName;

                        $element = "{!! Form::select('$name', [''] + \$$foreignModels,  Input::old('$name'), ['class' => 'form-control', ]) !!}";

                        $element .= "\n{!! Form::text('$plainName', $$camelModel ? $$camelModel->${plainName}->${foreign_display} : '', $readonlyHalf'data-vsch_completion'=>'$foreign_models:${foreign_display};id:$name','class' => 'form-control', $readonlyClose) !!}";

                        $elementFilter = "{!! Form::text('$foreign_model', Input::get('$foreign_model'), ['form' => 'filter-$models', 'data-vsch_completion'=>'$foreign_models:${foreign_display};id:$name','class'=>'form-control', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), ]) !!}";

                        if ($filterRows) {
                            $afterElementFilter .= "{!! Form::hidden('$name', Input::old('$name'), ['form' => 'filter-$models', 'id'=>'$name']) !!}";
                        } else {
                            $afterElementFilter .= "{!! Form::hidden('$name', Input::old('$name'), ['id'=>'$name']) !!}";
                        }

                        //$labelName = $foreignModelVars['model'];
                        //$labelGroup = $foreignModelVars['dash-models'];

                        if ($useOp) {
                            $afterElement .= "\n\t\n@if(\$op === 'create' || \$op === 'edit')";
                        }
                        $afterElement .= "\n\t<div class='form-group col-sm-1'>\n\t\t\t<label>&nbsp;</label>\n\t\t\t<br>\n\t\t\t<a href=\"@route('$foreignmodels.create')\" @linkAsButton('warning')>@lang('messages.create')</a>\n</div>";
                        if ($useOp) {
                            $afterElement .= "\n@endif";
                        }

                        $element .= "\n\t\t\t".$afterElementFilter;
                    } else {
                        $element = "{!! Form::input('number', '$name', Input::old('$name'), $readonly'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), $readonlyClose) !!}";
                        $elementFilter = "{!! Form::input('number', '$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), ]) !!}";
                    }
                    break;

                case 'text':
                    $limit = empty($limit) ? 256 : $limit;
                    $rowAttr = (int)($limit / 64) ?: 1;
                    $element = "{!! Form::textarea('$name', Input::old('$name'), $readonly'class'=>'form-control', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), 'rows'=>'$rowAttr', $readonlyClose) !!}";
                    $elementFilter = "{!! Form::text('$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), ]) !!}";
                    break;

                case 'boolean':
                    $element = "{!! Form::checkbox('$name', 1, Input::old('$name'), [$disabled]) !!}";
                    $elementFilter = "{!! Form::select('$name', ['' => '&nbsp;', '0' => '0', '1' => '1', ], Input::get('$name'), ['form' => 'filter-$models', 'class' => 'form-control', ]) !!}";
                    $useShort = true;
                    break;

                case 'date':
                case 'dateTime':
                    $element = <<<HTML
<div class="input-group input-group-sm date">
    {!! Form::text('$name', Input::old('$name'), $readonly'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), $readonlyClose) !!}
    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
</div>
HTML;
                    $elementFilter = <<<HTML
<div class="input-group date">
    {!! Form::text('$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), ]) !!}
    <span class="input-group-addon"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
</div>
HTML;
                    break;

                case  'decimal':
                case  'double':
                case  'float':
                case 'time':
                case 'string':
                default:
                    $element = "{!! Form::text('$name', Input::old('$name'), $readonly'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), $readonlyClose) !!}";
                    $elementFilter = "{!! Form::text('$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control$inputNarrow', 'placeholder'=>noEditTrans('$dash_models.$trans_name'), ]) !!}";
                    break;
            }

            if ($filterRows) {
                $afterElementFilter = $afterElementFilter ? "\n\t\t\t\t" . $afterElementFilter : $afterElementFilter;
                $frag = "\t\t\t\t<td>$elementFilter$afterElementFilter</td>";
            } elseif ($useShort) {
                if ($wrapRow) {
                    $frag = <<<EOT
        <div class="row">
                <label>
                      $element @lang('$labelGroup.$labelName')
                      &nbsp;&nbsp;
                </label>$afterElement
        </div>

EOT;
                } else {
                    $frag = <<<EOT
            <label>
                  $element @lang('$labelGroup.$labelName')
                  &nbsp;&nbsp;
            </label>$afterElement
EOT;
                }
            } else {
                if ($wrapRow) {
                    $frag = <<<EOT
        <div class="row">
            <div class="form-group col-sm-3">
                <label for="$name">@lang('$labelGroup.$labelName'):</label>
                  $element
            </div>
        </div>
        $afterElement
EOT;
                } else {
                    $frag = <<<EOT
        <div class="form-group">
            <label for="$name">@lang('$labelGroup.$labelName'):</label>
              $element$afterElement
        </div>

EOT;
                }
            }

            $formMethods[] = $frag;
        }

        return implode(PHP_EOL, $formMethods);
    }
}
