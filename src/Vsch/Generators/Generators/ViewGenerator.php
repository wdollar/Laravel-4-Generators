<?php

namespace Vsch\Generators\Generators;

use Illuminate\Support\Pluralizer;

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

        if ($this->needsScaffolding($template))
        {
            return $this->getScaffoldedTemplate($name);
        }

        // Otherwise, just set the file
        // contents to the file name
        return $name;
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
        $model = $this->cache->getModelName();  // post
        $models = Pluralizer::plural($model);   // posts
        $Models = ucwords($models);             // Posts
        $Model = Pluralizer::singular($Models); // Post
        $useLang = false;

        // Create and Edit views require form elements
        if (str_contains($this->template, '{{formElements}}'))
        {
            $formElements = $this->makeFormElements(false, false, false, false);
            $this->template = str_replace('{{formElements}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:readonly}}'))
        {
            $formElements = $this->makeFormElements(true, false, false);
            $this->template = str_replace('{{formElements:readonly}}', $formElements, $this->template);
        }

        // no booleans
        if (str_contains($this->template, '{{formElements:nobool}}'))
        {
            $formElements = $this->makeFormElements(false, false, true);
            $this->template = str_replace('{{formElements:nobool}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:nobool:readonly}}'))
        {
            $formElements = $this->makeFormElements(true, false, true);
            $this->template = str_replace('{{formElements:nobool:readonly}}', $formElements, $this->template);
        }

        // only booleans
        if (str_contains($this->template, '{{formElements:bool}}'))
        {
            $formElements = $this->makeFormElements(false, true, false);
            $this->template = str_replace('{{formElements:bool}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:bool:readonly}}'))
        {
            $formElements = $this->makeFormElements(true, true, false);
            $this->template = str_replace('{{formElements:bool:readonly}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:op}}'))
        {
            $formElements = $this->makeFormElements(false, false, false, true);
            $this->template = str_replace('{{formElements:op}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:bool:op}}'))
        {
            $formElements = $this->makeFormElements(false, true, false, true);
            $this->template = str_replace('{{formElements:bool:op}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:nobool:op}}'))
        {
            $formElements = $this->makeFormElements(false, false, true, true);
            $this->template = str_replace('{{formElements:nobool:op}}', $formElements, $this->template);
        }

        if (str_contains($this->template, '{{formElements:filters}}'))
        {
            $formElements = $this->makeFormElements(false, false, false, false, $models);
            $this->template = str_replace('{{formElements:filters}}', $formElements, $this->template);
        }

        // Replace template vars in view
        foreach ([ 'model', 'models', 'Models', 'Model' ] as $var)
        {
            $this->template = str_replace('{{' . $var . '}}', $$var, $this->template);
        }

        // And finally create the table rows
        if (str_contains($this->template, '{{headings:lang}}'))
        {
            $useLang = true;
            $this->template = str_replace('{{headings:lang}}', '{{headings}}', $this->template);
        }
        else
        {
        }

        list($headings, $fields, $editAndDeleteLinks) = $this->makeTableRows($model, $useLang);
        $this->template = str_replace('{{headings}}', implode(PHP_EOL . "\t\t\t\t", $headings), $this->template);
        $this->template = str_replace('{{fields}}', implode(PHP_EOL . "\t\t\t\t\t", $fields) . PHP_EOL . $editAndDeleteLinks, $this->template);
        $this->template = str_replace('{{fields:nobuttons}}', implode(PHP_EOL . "\t\t\t\t\t", $fields) . PHP_EOL, $this->template);

        return $this->template;
    }

    /**
     * Create the table rows
     *
     * @param  string $model
     *
     * @param         $useLang
     *
     * @return Array
     */
    protected
    function makeTableRows($model, $useLang)
    {
        $models = Pluralizer::plural($model); // posts

        $fields = $this->cache->getFields();

        // First, we build the table headings
        if ($useLang)
        {
            $headings = array_map(function ($field) use ($models)
            {
                return '<th>@lang(\'' . $models . '.' . $field . '\')</th>';
            }, array_keys($fields));
        }
        else
        {
            $headings = array_map(function ($field)
            {
                return '<th>' . ucwords($field) . '</th>';
            }, array_keys($fields));
        }

        // And then the rows, themselves
        $fields = array_map(function ($field) use ($model)
        {
            return "<td>{{{ \$$model->$field }}}</td>";
        }, array_keys($fields));

        // Now, we'll add the edit and delete buttons.
        $editAndDelete = <<<EOT
                    <td>
                        {{ Form::open(['style' => 'display: inline-block;', 'method' => 'DELETE', 'route' => ['{$models}.destroy', \${$model}->id, ], ]) }}
                            {{ Form::submit('Delete', ['class' => 'btn btn-danger', ]) }}
                        {{ Form::close() }}
                        {{ link_to_route('{$models}.edit', 'Edit', array(\${$model}->id), ['class' => 'btn btn-info',]) }}
                    </td>
EOT;

        return [ $headings, $fields, $editAndDelete ];
    }

    /**
     * Add Laravel methods, as string,
     * for the fields
     *
     * @param bool   $disable
     *
     * @param bool   $onlyBoolean
     * @param bool   $noBoolean
     * @param bool   $useOp
     * @param string $models
     *
     * @return string
     * @internal param $model
     */
    public
    function makeFormElements($disable = false, $onlyBoolean = false, $noBoolean = false, $useOp = false, $models = '')
    {
        $formMethods = [ ];
        $filterRows = !empty($models);

        if ($useOp)
        {
            $readonly = '$op === \'view\' ? \'readonly\' : \'\',';
            $disabled = '$op === \'view\' ? \'disabled\' : \'\'';
        }
        else
        {
            $readonly = $disable ? "'readonly', " : '';
            $disabled = $disable ? "'disabled', " : '';
        }

        foreach ($this->cache->getFields() as $name => $type)
        {
            $formalName = ucwords($name);
            $limit = null;
            $useShort = false;

            if (str_contains($type, '['))
            {
                preg_match('/([^\[]+?)\[(\d+)\]/', $type, $matches);
                $type = $matches[ 1 ]; // string
                $limit = $matches[ 2 ]; // 50
            }

            if ($type === 'string' && $limit > 64 && !array_key_exists($name, [
                            'email' => '',
                            'name' => '',
                            'password' => '',
                            'password_confirmation' => ''
                   ])
            )
            {
                // treat it as text with multiple rows
                $type = 'text';
            }

            // TODO: add remaining types
            // TODO: if given disabled, genereate readonly form elements.
            if ($type === 'boolean' && $noBoolean) continue;
            if ($type !== 'boolean' && $onlyBoolean) continue;

            $labelName = $name;
            $afterElement = '';

            switch ($type)
            {
                case 'integer':

                    if (substr($name, strlen($name) - 3) === '_id')
                    {
                        // assume foreign key
                        $foreignModel = substr($name, 0, strlen($name) - 3);
                        $foreignModels = Pluralizer::plural($foreignModel);   // posts

                        $element = "<br>{{ Form::select('$name', [''] + \$$foreignModels,  Input::old('$name'), ['class' => 'input-xs btn-default', ]) }}";
                        $elementFilter = "{{ Form::select('$name', [''] + \$$foreignModels, Input::get('$name'), ['form' => 'filter-$models', 'class' => 'input-xs btn-default', ]) }}";

                        $labelName = $foreignModel;

                        $afterElement = "";
                        if ($useOp)
                        {
                            $afterElement .= "\n@if(\$op === 'create' || \$op === 'edit')";
                        }
                        $afterElement .= "\n\t\t&nbsp;&nbsp;<a href=\"{{ URL::route('$foreignModel.create') }}\" role=\"button\" class=\"btn btn-sm btn-warning\">@lang('messages.create')</a>";
                        if ($useOp)
                        {
                            $afterElement .= "\n@endif";
                        }
                    }
                    else
                    {
                        $element = "{{ Form::input('number', '$name', Input::old('$name'), [$readonly'class'=>'form-control', 'placeholder'=>trans('messages.$name'), ]) }}";
                        $elementFilter = "{{ Form::input('number', '$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control input-xs', 'placeholder'=>trans('messages.$name'), ]) }}";
                    }
                    break;

                case 'text':
                    $limit = empty($limit) ? 256 : $limit;
                    $rowAttr = (int)($limit / 64) ?: 1;
                    $element = "{{ Form::textarea('$name', Input::old('$name'), [$readonly'class'=>'form-control', 'placeholder'=>trans('messages.$name'), 'rows'=>'$rowAttr', ]) }}";
                    $elementFilter = "{{ Form::text('$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control input-xs', 'placeholder'=>trans('messages.$name'), ]) }}";
                    break;

                case 'boolean':
                    $element = "{{ Form::checkbox('$name', 1, Input::old('$name'), [$disabled, ]) }}";
                    $elementFilter = "{{ Form::select('$name', ['' => '&nbsp;', '0' => '0', '1' => '1', ], Input::get('$name'), ['form' => 'filter-$models', 'class' => 'input-xs btn-default', ]) }}";
                    $useShort = true;
                    break;

                default:
                    $element = "{{ Form::text('$name', Input::old('$name'), [$readonly'class'=>'form-control', 'placeholder'=>trans('messages.$name'), ]) }}";
                    $elementFilter = "{{ Form::text('$name', Input::get('$name'), ['form' => 'filter-$models', 'class'=>'form-control input-xs', 'placeholder'=>trans('messages.$name'), ]) }}";
                    break;
            }

            if ($filterRows)
            {
                $frag = <<<EOT
            <td>$elementFilter</td>
EOT;
            }
            elseif ($useShort)
            {
                $frag = <<<EOT
            <label>
                  $element @lang('messages.$labelName')
                  &nbsp;&nbsp;
            </label>$afterElement
EOT;
            }
            else
            {

                // Now that we have the correct $element,
                // We can build up the HTML fragment
                $frag = <<<EOT
        <div class="form-group">
            {{ Form::label('$name', trans('messages.$labelName') . ':') }}
              $element$afterElement
        </div>

EOT;
            }

            $formMethods[ ] = $frag;
        }

        return implode(PHP_EOL, $formMethods);
    }
}
