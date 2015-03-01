<?php

namespace Vsch\Generators\Generators;

use Illuminate\Support\Pluralizer;

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
        if (!$fields = $this->cache->getFields())
        {
            return str_replace('{{rules}}', '', $this->template);
        }

        $model = $this->cache->getModelName();  // post
        $models = Pluralizer::plural($model);   // posts
        $Models = ucwords($models);             // Posts
        $Model = Pluralizer::singular($Models); // Post

        foreach (['model', 'models', 'Models', 'Model', 'className'] as $var)
        {
            $this->template = str_replace('{{' . $var . '}}', $$var, $this->template);
        }

        if (strpos($this->template, '{{field:unique}}') !== false)
        {
            $uniqueField = '';
            foreach ($fields as $field => $type)
            {
                if (strpos('unique()', $type))
                {
                    $uniqueField = $field;
                    break;
                }
            }
            if ($uniqueField === '') $uniqueField = 'id';

            $this->template = str_replace('{{field:unique}}', $uniqueField, $this->template);
        }

        if (($pos = strpos($this->template, '{{field:line}}')) !== false)
        {
            // grab the line that contains
            $startPos = strrpos($this->template, "\n", -(strlen($this->template) - $pos));
            if ($startPos === false) $startPos = -1;

            $endPos = strpos($this->template, "\n", $pos);
            if ($endPos === false) $endPos = strlen($this->template)+1;

            $line = substr($this->template, $startPos+1, $endPos - $startPos - 1);

            $fieldText = '';
            foreach ($fields as $field => $type)
            {
                $fieldText .= str_replace('{{field:line}}', $field, $line) . "\n";
            }

            $this->template = substr($this->template, 0, $startPos+1) . $fieldText . substr($this->template, $endPos+1);
        }

        $rules = array_map(function ($field) use($fields)
        {
            $suffix = '';
            switch ($field)
            {
                case 'email' : $suffix .= '|email'; break;
                default:
                break;
            }

            switch ($fields[ $field ])
            {
                case 'boolean' : $suffix .= '|boolean'; break;
                case 'integer' : $suffix .= '|numeric'; break;  // |min:1|max:1000
            }

            // here we override for foreign keys
            if (substr($field, strlen($field) - 3) === '_id')
            {
                // assume foreign key
                $foreignModel = substr($field, 0, strlen($field) - 3);
                $foreignModels = Pluralizer::plural($foreignModel);   // posts
                $suffix = "|numeric|exists:$foreignModels,id";
            }

            return "'$field' => 'required$suffix'";
        }, array_keys($fields));

        $fieldText = '';
        foreach ($fields as $field => $type)
        {
            if ($field == 'id') continue;
            if ($fieldText) $fieldText .= ', ';
            $fieldText .= $field . ":" . $type;
        }
        $this->template = str_replace('{{fields}}', $fieldText, $this->template);

        return str_replace('{{rules}}', PHP_EOL . "\t\t" . implode(',' . PHP_EOL . "\t\t", $rules) . PHP_EOL . "\t", $this->template);
    }
}
