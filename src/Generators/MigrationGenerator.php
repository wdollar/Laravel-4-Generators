<?php

namespace Vsch\Generators\Generators;

use Illuminate\Filesystem\Filesystem as File;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Vsch\Generators\Cache;
use Vsch\Generators\GeneratorsServiceProvider;

class MigrationGenerator extends Generator
{

    // just the base path
    protected static $templatesDir;
    protected $tableName;

    function __construct(File $file, Cache $cache)
    {
        parent::__construct($file, $cache);
        static::$templatesDir = 'migration/';
    }

    /**
     * Fetch the compiled template for a migration
     *
     * @param  string $template Path to template
     * @param  string $name
     *
     * @return string Compiled template
     */
    protected
    function getTemplate($template, $name)
    {
        // We begin by fetching the master migration stub.
        $stub = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration.txt'));

        // Next, set the migration class name
        $stub = str_replace('{{name}}', Str::studly($name), $stub);

        // Now, we're going to handle the tricky
        // work of creating the Schema
        $upMethod = $this->getUpStub();
        $downMethod = $this->getDownStub();

        // Finally, replace the migration stub with the dynamic up and down methods
        $stub = str_replace('{{up}}', $upMethod, $stub);
        $stub = str_replace('{{down}}', $downMethod, $stub);

        $prefix = $this->options('prefix');
        $package = $this->options('bench');
        $stub = GeneratorsServiceProvider::replacePrefixTemplate($prefix, $package, $stub);

        return $this->replaceStandardParams($stub);
    }

    /**
     * Parse the migration name
     *
     * @param  string $name
     * @param  array  $fields
     *
     * @return MigrationGenerator
     */
    public
    function parse($name, $fields)
    {
        list($action, $tableName) = $this->parseMigrationName($name);

        $this->action = $action;
        $this->tableName = $tableName;
        $this->fields = $fields;

        return $this;
    }

    /**
     * Parse some_migration_name into array
     *
     * @param string $name
     *
     * @return array
     */
    protected
    function parseMigrationName($name)
    {
        // create_users_table
        // add_user_id_to_posts_table
        // create_post_tag_table
        $pieces = explode('_', $name);

        // This is the action that the user
        // wants to take. Create or Delete or Add.
        $action = array_shift($pieces);

        // Adding _table to the migration name is optional
        if (end($pieces) == 'table') array_pop($pieces);

        // Next, we need to determine what the table name is.
        // This is tough, because it could be something like
        // posts, or posts_tags. Further, the migration name could
        // be 'create_posts_tags_table', or 'add_post_id_to_posts_tags_table'
        // So we'll search for the keywords 'to' or 'from'.
        $divider = array_search('to', $pieces);
        if ($divider === false) $divider = array_search('from', $pieces);

        // If we did find one of those "to" or "from" connecting words,
        // we know that what follows is the table name.
        $tableName = ($divider !== false)
            ? implode('_', array_slice($pieces, $divider + 1))
            : implode('_', $pieces);

        // For example: ['add', 'posts']
        return array($action, $tableName);
    }

    /**
     * Grab up method stub and replace template vars
     *
     * @return string
     */
    protected
    function getUpStub()
    {
        switch ($this->action) {
            case 'add':
            case 'insert':
                $upMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-up.txt'));
                $fields = $this->fields ? $this->setFields('addColumn') : '';
                break;

            case 'remove':
            case 'drop':
            case 'delete':
                $upMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-up.txt'));
                $fields = $this->fields ? $this->setFields('dropColumn') : '';
                break;

            case 'pivot':
                $upMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-up-pivot.txt'));
                $fields = $this->fields ? $this->setFields('addColumn') : '';
                break;

            case 'destroy':
                $upMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-up-drop.txt'));
                $fields = $this->fields ? $this->setFields('dropColumn') : '';
                break;

            case 'create':
            case 'make':
            default:
                $upMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-up-create.txt'));
                $fields = $this->fields ? $this->setFields('addColumn') : '';
                break;
        }

        // Replace the tableName in the template
        $upMethod = str_replace('{{tableName}}', $this->tableName, $upMethod);

        // Insert the schema into the up method
        return str_replace('{{methods}}', $fields, $upMethod);
    }

    /**
     * Grab down method stub and replace template vars
     *
     * @return string
     */
    protected
    function getDownStub()
    {
        switch ($this->action) {
            case 'add':
            case 'insert':
                // then we to remove columns in reverse
                $downMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-down.txt'));
                $fields = $this->fields ? $this->setFields('dropColumn') : '';
                break;

            case 'remove':
            case 'drop':
            case 'delete':
                // then we need to add the columns in reverse
                $downMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-down.txt'));
                $fields = $this->fields ? $this->setFields('addColumn') : '';
                break;

            case 'destroy':
                // then we need to create the table in reverse
                $downMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-down-create.txt'));
                $fields = $this->fields ? $this->setFields('addColumn') : '';
                break;

            case 'create':
            case 'make':
            default:
                // then we need to drop the table in reverse
                $downMethod = $this->file->get(GeneratorsServiceProvider::getTemplatePath(self::$templatesDir, 'migration-down-drop.txt'));
                $fields = $this->fields ? $this->setFields('dropColumn') : '';
                break;
        }

        // Replace the tableName in the template
        $downMethod = str_replace('{{tableName}}', $this->tableName, $downMethod);

        // Insert the schema into the down method
        return str_replace('{{methods}}', $fields, $downMethod);
    }

    /**
     * Create a string of the Schema fields that
     * should be inserted into the sub template.
     *
     * @param string $method (addColumn | dropColumn)
     *
     * @return string
     */
    protected
    function setFields($method = 'addColumn')
    {
        $fields = $this->convertFieldsToArray();

        $template = array_map(array($this, $method), $fields);

        return implode("\n\t\t\t", $template);
    }

    /**
     * If Schema fields are specified, parse
     * them into an array of objects.
     *
     * So: name:string, age:integer
     * Becomes: [ ((object)['name' => 'string'], (object)['age' => 'integer'] ]
     *
     * @returns mixed
     */
    protected
    function convertFieldsToArray()
    {
        $fields = $this->fields;

        if (!$fields) return;

        $fields = GeneratorsServiceProvider::splitFields($fields, true);

        $indices = [0]; // first element is last used index number, keys are _i where i is passed from the parameters, or auto generated, _i => _n_f where n is from params and f index of the field in the fields list
        $keyindices = $indices; // first element is last used index number, keys are _i where i is passed from the parameters, or auto generated, _i => _n_f where n is from params and f index of the field in the fields list
        $primaryindices = $indices; // first element is last used index number, keys are _i where i is passed from the parameters, or auto generated, _i => _n_f where n is from params and f index of the field in the fields list
        $dropIndices = [];
        $foreignKeys = [];

        $relationsModelList = GeneratorsServiceProvider::getRelationsModelVarsList($fields);

        $fieldIndex = 0;
        foreach ($fields as $field) {
            $fieldIndex++;

            // If there is a third key, then
            // the user is setting any number
            // of options
            $options = $field->options;
            $field->options = '';
            $hadUnsigned = false;
            $hadNullable = false;
            $hadDefault = false;
            $foreignTable = null;

            foreach ($options as $option) {
                if (($isPrimary = strpos($option, 'primary') === 0) || ($isKey = strpos($option, 'keyindex') === 0) || strpos($option, 'index') === 0) {
                    if ($isPrimary) $keyIndex = &$primaryindices;
                    elseif ($isKey) $keyIndex = &$keyindices;
                    else $keyIndex = &$indices;

                    $this->processIndexOption($keyIndex, $option, $field->name, $fieldIndex);
                }

                if (GeneratorsServiceProvider::isFieldHintOption($option)) continue;

                if ($option === 'unsigned' || $option === 'unsigned()') {
                    $hadUnsigned = true;
                }

                if ($option === 'nullable' || $option === 'nullable()') {
                    $hadNullable = true;
                }

                if ($option === 'default' || starts_with($option, 'default(')) {
                    if ($option === 'default') $option = 'default(null)';
                    $hadDefault = true;
                }

                $field->options .= (str_contains($option, '(')) ? "->{$option}" : "->{$option}()";
            }

            // add foreign keys
            $name = $field->name;
            if (array_key_exists($name, $relationsModelList)) {
                $table_name = $relationsModelList[$name]['snake_models'];
                if (!$hadUnsigned) $field->options .= "->unsigned()";
                $indexName = "ixf_{$this->tableName}_{$name}_{$table_name}_id";

                if (strlen($indexName) > 64) {
                    $indexName = substr($indexName, 0, 64);
                }

                $foreignKeys[] = "\$table->foreign('$name','$indexName')->references('id')->on({{prefix}}'$table_name')";
                $dropIndices[] = "\$table->dropIndex('$indexName')";
            }

            if ($hadNullable && !$hadDefault && !$field->type === 'text') {
                $field->options .= "->default(null)";
            }
        }

        // now append the indices
        $inds = $foreignKeys;
        $inds = array_merge($inds, $this->generateIndex('index', $indices, $dropIndices));
        $inds = array_merge($inds, $this->generateIndex('unique', $keyindices, $dropIndices));
        $inds = array_merge($inds, $this->generateIndex('primary', $primaryindices, $dropIndices));
        $fields[] = [$inds, $dropIndices];
        return $fields;
    }

    private
    function generateIndex($type, $indices, &$dropIndices)
    {
        // skip the auto counter
        if (count($indices) === 1) return [];
        array_shift($indices);

        $sortedKeys = array_keys($indices);
        sort($sortedKeys);
        $indexTexts = [];
        foreach ($sortedKeys as $sortedKey) {
            $sortedFieldKeys = array_keys($indices[$sortedKey]);
            sort($sortedFieldKeys);
            $fields = [];

            foreach ($sortedFieldKeys as $sortedFieldKey) {
                $fields[$indices[$sortedKey][$sortedFieldKey]] = "'" . $indices[$sortedKey][$sortedFieldKey] . "'";
            }

            $indexName = ($type === 'primary' ? "pk_" : ($type === 'unique' ? "ixk_" : "ix_")) . $this->tableName . "_" . implode('_', array_keys($fields));
            $dropIndices[] = "\$table->dropIndex('$indexName')";
            $indexTexts[] = "\$table->$type([" . implode(',', $fields) . "], '$indexName')";
        }

        return $indexTexts;
    }

    private
    function processIndexOption(&$indices, $option, $field, $fieldIndex)
    {
        $params = preg_match('/\((.*)\)/', $option, $matches) ? $matches[1] : '';
        $i = null;
        $n = '';
        $f = $fieldIndex;
        if ($params !== '') {
            $params = explode(',', $params);
            $i = (int)$params[0];
            $n = count($params) > 1 ? (int)$params[1] : null;
        }

        if ($i === null) {
            // make a new one
            $i = sprintf("%03d", ++$indices[0]) . "_";
        }
        else {
            if ($i > $indices[0]) $indices[0] = $i;
            $i = sprintf("%03d", (int)$i);
        }

        $key = "_$i";
        if (!array_key_exists($key, $indices)) $indices[$key] = [];
        $n = sprintf("%03d", (int)$n);
        $f = sprintf("%03d", (int)$f);
        $indices[$key]["_{$n}_$f"] = $field;
    }

    /**
     * Return template string for adding a column
     *
     * @param string $field
     *
     * @return string
     */
    protected
    function addColumn($field)
    {
        // Let's see if they're setting
        // a limit, like: string[50]
        if (is_array($field)) {
            return empty($field[0]) ? '' : implode(";\n\t\t\t", $field[0]) . ';';
        }

        if (str_contains($field->type, '[')) {
            preg_match('/([^\[]+?)\[(\d+(?:\,\d+)?)\]/', $field->type, $matches);
            $field->type = $matches[1]; // string
            $field->limit = $matches[2]; // 50 or 6,2
        }

        // We'll start building the appropriate Schema method
        $html = "\$table->{$field->type}";

        $html .= isset($field->limit)
            ? "('{$field->name}', {$field->limit})"
            : "('{$field->name}')";

        // Take care of any potential indexes or options
        if (isset($field->options)) {
            $html .= $field->options;
        }

        return $html . ';';
    }

    /**
     * Return template string for dropping a column
     *
     * @param string $field
     *
     * @return string
     */
    protected
    function dropColumn($field)
    {
        return is_array($field) ? (empty($field[1]) ? '' : implode(";\n\t\t\t", $field[1]) . ";") : "\$table->dropColumn('" . $field->name . "');";
    }

    protected
    function getPath($path)
    {
        $migrationFile = strtolower(basename($path));
        return GeneratorsServiceProvider::uniquify(dirname($path) . '/' . date('Y_m_d_His') . '*_' . $migrationFile);
    }

}
