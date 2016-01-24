## Version Notes

The 1.x.x versions are for Laravel 4.2, 2.x.x versions are for Laravel 5.1

### x.3.3

- change view generator for selection of foreign model id to use `[0 => ''] + $modelList` instead of `[''] + $modelList`

### x.3.2

- add `bitset(bitName1,bitName2,...)` field option. Converted integral field in the database to bit fields in the model, with getter/setter methods for individual bitNames and in forms as checkbox elements using the model's, bit name to bit mask type, to iterate over the bits. That way you can easily add fields to the bit set after creating the scaffold. 

    For example in `promotion` model a field definition: `flags:bitset(is_published,one_per_user)` has the following macro expansions in corresponding generators:

    ##### ModelGenerator 

    `{{bitset:fields}}` : a comma separated list of bitset fields in the model, in this example expands to `'flags'`. so `[{{bitset:fields}}]` will define an array of names of bitset fields in the model.
    
    `{{bitset:maps}}` : an assoc array entry for bitset field names to the array that maps their bit names to bit masks in the model, in this example expands to `'flags' => self::flags_types`. so `[{{bitset:maps}}]` will define an array of bitset field to bit name/mask map in the model. Use in conjunction with `{{bitset:data}}`.
    
    `{{bitset:data}}` : expands to define bit masks, bit names and a map from name to mask. 
    
    ```php
    const FLAGS_NONE = '';
    const FLAGS_IS_PUBLISHED = 'is_published';
    const FLAGS_ONE_PER_USER = 'one_per_user';
    
    const FLAGS_NONE_MASK = 0;
    const FLAGS_IS_PUBLISHED_MASK = 1;
    const FLAGS_ONE_PER_USER_MASK = 2; 
    
    public static $flags_types = [
        self::FLAGS_IS_PUBLISHED => self::FLAGS_IS_PUBLISHED_MASK,
        self::FLAGS_ONE_PER_USER => self::FLAGS_ONE_PER_USER_MASK,
    ];
    ```

    `{{bitset:attributes}}` : expands to define getter/setter attributes for these bit fields.
    
    ```php
    /**
     * @return boolean
     */
    public
    function getIsPublishedAttribute()
    {
        return !!($this->flags & self::FLAGS_IS_PUBLISHED_MASK);
    }

    /**
     * @param boolean $value
     */
    public
    function setIsPublishedAttribute($value)
    {
        if ($value) {
            $this->flags |= self::FLAGS_IS_PUBLISHED_MASK;
        } else {
            $this->flags &= ~self::FLAGS_IS_PUBLISHED_MASK;
        }
    }

    /**
     * @return boolean
     */
    public
    function getOnePerUserAttribute()
    {
        return !!($this->flags & self::FLAGS_ONE_PER_USER_MASK);
    }

    /**
     * @param boolean $value
     */
    public
    function setOnePerUserAttribute($value)
    {
        if ($value) {
            $this->flags |= self::FLAGS_ONE_PER_USER_MASK;
        } else {
            $this->flags &= ~self::FLAGS_ONE_PER_USER_MASK;
        }
    }
    ```
    
    ##### ControllerGenerator 

    `{{bitset:line}}` marks a line that is to be repeated for every bit set field in the model, with the marker itself removed during expansion. It can no anywhere in the line. In addition to the `{{modelVars}}` which expand to the various case and plural versions of the model you have `{{bitset:modelVars}}` which will do the same for the bitset field name:
    
    since this affect only one line use the `{{eol}}` to mark where the lines are split in the final file. 
    
    ```
    ${{bitset:field}} = 0; {{eol}} foreach ({{CamelModel}}::${{bitset:field}}_bitset as $type => $flag) { {{eol}} if (array_key_exists($type, $input))  { {{eol}} ${{bitset:field}} |= $flag; {{eol}} } {{eol}} } {{eol}} $input['{{bitset:field}}'] = ${{bitset:field}};   {{bitset:line}}
    ```
    
    To make it easier to read I replaced `{{eol}}` with line breaks and removed the `{{bitset:line}}` marker, otherwise it is what the above line has:
    
    ```
    ${{bitset:field}} = 0;  
    foreach ({{CamelModel}}::${{bitset:field}}_bitset as $type => $flag) {  
        if (array_key_exists($type, $input))  {  
            ${{bitset:field}} |= $flag;  
        }  
    }  
    $input['{{bitset:field}}'] = ${{bitset:field}};
    ```

    will expand to:
    
    ```php
    $flags = 0;
    foreach (Promotion::$flags_bitset as $type => $flag) {
        if (array_key_exists($type, $input)) {
            $flags |= $flag;
        }
    }
    $input['flags'] = $flags;
    ```
    
    You can use this in the controller where form inputs are being processed, prior to validation, with `$input` holding all the inputs of the request. I use simpler code that calls a helper function that does the same thing:
    
        processFlags($input, Promotion::$flag_bitset, 'flags');
        
    
    ##### ViewGenerator 
    
    `{{headings:lang}}` expands to the table column headers for the bit names using @lang() for the text:
    
    ```blade
    @foreach(app\Promotion::$flags_bitset as $type => $flag)
    <th>@lang('promotions.'.$type)</th>
    @endforeach
    ```

    `{{headings}}` expands to the column headers using the bit names for the text:
    
    ```blade
    @foreach(app\Promotion::$flags_bitset as $type => $flag)
    <th>{{ucwords(str_replace('_', ' ', $type))}}</th>
    @endforeach
    ```

    `{{formElements:filters}}` expands to:
    
    ```blade
    @foreach(app\Promotion::$flags_bitset as $type => $flag)
    <td>{!! \Form::select($type, ['' => '&nbsp;', '0' => '0', '1' => '1', ], 
            Input::get($type), 
            ['form' => 'filter-promotions', 'class' => 'form-control', ]) !!}</td>
    @endforeach
    ```

    `{{fields:nobuttons}}` and `{{fields}}` will contain the following for the `flags` field:

    ```blade
    @foreach(app\Promotion::$flags_bitset as $type => $flag)
    <td>{{ $promotion->$type }}</td>;
    @endforeach
    ```

    `{{formElements:bool:op}}` expands to contain all boolean fields and all bitset field bit names, for `flags` it will be:
    
    ```blade
    @foreach(app\Promotion::$flags_bitset as $type => $flag)
    <label>
        {!! Form::checkbox($type, 1, Input::old($type), 
            [isViewOp($op) ? 'disabled' : '',]) !!} @lang('promotions.flags')&nbsp;&nbsp;
    </label>
    @endforeach
    ```

    `{{formElements:bool}}` expands to contain all boolean fields and all bitset field bit names, for `flags` it will be:
    
    ```blade
    @foreach(app\Promotion::$flags_bitset as $type => $flag)
    <label>
        {!! Form::checkbox($type, 1, Input::old($type), []) !!} 
        @lang('promotions.flags')&nbsp;&nbsp;
    </label>
    @endforeach
    ```


    ##### TranslationsGenerator
    
    Individual bit names are treated as if they were declared as boolean fields. So an entry for `'snake_case_bit_name' => 'Snake Case Bit Name'`, so for the example field above:
    
    `{{translations:line}}` will have entries for both the field and its bit fields added:
    
    ```php
    'flags' => 'Flags',
    'is_published' => 'Is Published',
    'one_per_user' => 'One Per User',
    ```

- add `table`, `field` to foreign relations map which represent the table name, if given in foreign() hint, and field name that refers to this foreign key, respectively.

- fix `{{field:unique}}` to make the field list from the first unique index defined for the model.

- add `ondelete` field hint in MigrationGenerator for foreign keys to add `onDelete('cascade')` to foreign key declaration.

- fix model generator to not add 'required' to rules if `rule(sometimes)` or `default(...)` field hints are present.

- doc view generator for scaffolds with foreign fields produces both an input select and an input text with typeahead for entering foreign ids. One of these needs to be commented out depending on the application and the desired input type. Typeahead requires server support for dynamically determining completions.

- fix translation generator not to add duplicated keys.

- fix controller generator not to add duplicated `{{relations}}`, `{{relations:line}}` and `{{relations:line:with_model}}`.

- add `{{app_namespace}}` to all generators to be replaced by the configured `\App::getNamespace()`, without the trailing `\` so usage is `{{app_namespace}}\...`

- add `{{eol}}` to all generators to be replaced by `\n`.

- add `{{relations:line:with_model}}` to controller generator to create lines for foreign relationships but also include the self model into the list. Used for creating import statements for related models and own model. For example a line in the `templates/scaffold/controller.txt`:

        use {{relations:line:with_model}}{{app_namespace}}\{{relations:CamelModel}};

    once expanded changes to the following, depending on the model's foreign key fields of course:
    
    ```php
    use app\License;
    use app\Product;
    use app\User;
    ```

- add `--lang=path` to `generate:translation` for scaffold use. Any *.txt files in the `template/scaffold/lang/` directory will convert the model vars as per Model Vars Table and add those translation definitions to the corresponding .php file in the `lang/en/` directory. Allows to create place-holder translations based on the model generated. 

    The template once processed should evaluate to a set of array `key=>value` definitions as would be used in translation files. The `return array( ... )` wrapper is added by the code.

    For example, I use the following in `template/lang/page-titles.txt`:
    
    ```php
    'index-{{dash-models}}' => 'Index {{Space Models}}',
    'create-{{dash-model}}' => 'Create {{Space Model}}',
    'show-{{dash-model}}' => 'Show {{Space Model}}',
    'edit-{{dash-model}}' => 'Edit {{Space Model}}',
    'delete-{{dash-model}}' => 'Delete {{Space Model}}',
    ```

    For example, when generating a scaffold for a model named `productVersion` this template has the effect of adding the following translations to `resources/lang/en/page-titles.php`:
    
    ```php
    'create-product-version' => 'Create Product Version',
    'delete-product-version' => 'Delete Product Version',
    'edit-product-version'   => 'Edit Product Version',
    'index-product-versions' => 'Index Product Versions',
    'show-product-version'   => 'Show Product Version',
    ```

    Existing translations are not overwritten, unless `--overwrite` option is used. Comments are preserved and location of keys under particular block comment is also preserved. I use the `TranslationFileRewriter` class from my laravel-translation-manager package to do surgical insertion of new translations without loosing the comments and position of translations. 

    This is a convenient way of adding model related translations to existing files.

- add a numeric sequence to migration file name after the Hms, when running scaffold creation from a batch file multiple migrations are created within the same second and then the migrations would be applied alphabetically, not in the order of creation. Causing errors when foreign keys were on tables not yet created.

- add `foreign(table_name,id,name)` field hint to give the table name for an field name for foreign keys, optional id: foreign id column (default is id), and foreign displayable column to use for UI selections (default name), so that the foreign table can be explicitly provided instead of guessing that it is the plural form of the field name without the _id suffix. For now only integer and bigInteger foreign keys are implemented. A few more iterations of cleanup and other types will be included too.

- add all generators now recognize foreign keys when the field ends in `_id` or when a `foreign()` field hint is provided. Only integer and bitInteger field types are supported for now.

- add `--overwrite` option to all generators so that if the file exists then it will be overwritten instead of creating a file with .new extension. Recommended use during initial honing of the templates and generated scaffolds, afterwards you should not use this option to eliminate the possibility of overwriting your files by accident. 

- add `--bench="name/package"` option to all generator commands, generated code will be added to the given name/package in the workbench. A convenient way to scaffold models, controllers and migrations in packages on which you are working in the project workbench directory.

- add `--prefix="prefix_"` option to migration and model generator commands, generated code will add a prefix to the table names of the model. Additionally, with `--bench` option the prefix will be taken from the package's `config.table_prefix` configuration via `\Config::get('package::config.table_prefix','')`. `--prefix` option has precedence over `--bench`. If non-empty `--prefix` is specified then it will be used instead of the package config setting, for both the model and the migration files.

- add `primary(n,m)` field definition. This is the same as `index(n,m)` and `keyindex(n,m)` but will create a primary index for the fields. See: [index hint](#IndexHint)

- add `{{relation:line}}` to model generator, it will repeat the line containing this tag for every foreign relation of the model, while replacing `{{relation:var_name}}` where `var_name` is one of the field names from the [Model Vars Table](#ModelVarsTable) table below. For example:

lines in model.txt:

```php
public static $remote_relations = array(
    {{relation:line}}'{{relation:snake_model}}'=>['{{relation:snake_model}}_id', 'id'],
);
```

for a model that has two foreign key fields: `sender_id` and `conversation_id` will have the following resulting code: 

```php
public static $remote_relations = array(
    'sender'=>['sender_id', 'id'],
    'conversation'=>['conversation_id', 'id'],
);
```    

for a model that has no foreign key fields the line is omitted:

```php
public static $remote_relations = array(
);
```    

### 1.3.1

- rewrote field string parsing to handle nested (),[] and {}. Now field options can have comma separated parameters. 

- add `default(..)` hint now adds to the `{{defaults}}` placeholder. Use in the model.txt template to create an associative array of field name to default value. This can be used to provide defaults to the form when creating and also to fill in default values and replace empty strings by nulls for numeric and date/datetime fields. More docs on the subject are in the future.

- add `rule(....)` hint. Adds the stuff between parentheses to the field's rules. Some rules are added automatically. Numeric fields get `numeric`, if the field is not nullable then it gets `required`. email will get `email|unique|table:email,id,{id}` added, the `{id}` placeholder should be changed to the id of the model when it is being saved to prevent triggering a unique e-mail validation failure.

- add <a id="IndexHint"></a>index hint: `index(indexdef)` to create an index and `keyindex(indexdef)` to create a unique index, in the migration file. `indexdef` has the format: `i,n`, where `i` and `n` are integers, `i` is the index id, `n` is the position of the field in the index. If `n` is not given then the position will be the order of the field's appearance. Used to automatically add indices to table creation script in migration file. Multiple fields can specify the same index id and can have multiple index hints with different ids. 

    ```
    user_name:string[24]:keyindex(1,1)
    , city:string[32]:index(2,2)
    , state:string[32]:index(2,1) 
    ```

    will create a table with two indices: unique index on user_name and a non-unique index on state,city.

- doc forgot to document new field types being handled in version 1.3.0 and added hints to model fields. Hints are used by the generators but stripped out of the migration file.

    hints are added as options after the field type with additional `:` separating them. Any options that do not have a `(` get `()` added on. This was pre-existing functionality. 

    Additionally, the generators recognize and use standard options: nullable, default to affect the generated code. Some shortcut names are added to reduce typing

| shortcut type | Laravel type                                                                                                      |
| :------------ | :---------------------------------------------------------------------------------------------------------------- |
| int           | integer                                                                                                           |
| tinyint       | tinyInteger                                                                                                       |
| smallint      | smallInteger                                                                                                      |
| medint        | mediumInteger                                                                                                     |
| mediumint     | mediumInteger                                                                                                     |
| bigint        | bigInteger                                                                                                        |
| bool          | boolean                                                                                                           |
| datetime      | dateTime                                                                                                          |
| decimal       | decimal, except specify the parameter as n.m instead of n,m, ie. `decimal(6,2)` should be given as decimal\[6.2\] |
[**Field Shortcut Types to Laravel Type mappings**]

| type(s)           | effect in code                                                                                                                                                                         |
| :---------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| date, dateTime    | wraps the field in a div.form-group.date, adds a span.input-group-addon with a calendar glyphicon. If you include bootstrap-datepicker then this gives a pop-up calendar for the field |
| all integer types | generates a number field in views, int and bigint are treated as foreign keys if they have an \_id suffix in the field name                                                            |
| boolean           | generates a checkbox field in views, also used for :bool and :nobool expansion placeholders                                                                                            |
[**Type's Effect in Code**]


| hint            | effect                                                                                     |
| :-------------- | :----------------------------------------------------------------------------------------- |
| hidden          | adds the field name to the `{{hidden}}` fields list placeholder                            |
| guarded         | adds the field name to the `{{guarded}}` fields list placeholder                           |
| notrail         | adds the field name to the `{{notrail}}` fields list placeholder                           |
| notrailonly     | adds the field name to the `{{notrailonly}}` fields list placeholder                       |
| textarea        | uses textarea for the field in the view generator instead of text                          |
| index(indexdef) | creates a non-unique index (index) which includes the field, see notes for indexdef format |
| keyindex        | creates a unique index which includes the field, see notes for indexdef format             |
[**Hint's effect on code generation**]

Usage:  `field_name:int:hidden:guarded`, or `field_name:string[256]:textarea:notrail`

### 1.3.0

#### These are specific to my use and will not work without adding some support code

- change view generator `{{formElements:op}}` to generate elements as readonly or disabled (for checkboxes) based on a variable $op wrapped in isViewOp($op) function. That way the code that determines when the fields should be readonly can be isolated from the generator.

- change view generator for admin.txt generates three types of fields for foreign keys: select and text. The select is populated from a variable based on the camelCaseModels foreign name. The text field and a hidden field are intended to be used together. The hidden field holds the id of the foreign model and the text field a human readable form. I use the typeahead jQuery plugin, with some server code, to resolve the human readable field to the id expected by the database. 

#### These are fairly generic

- fix trailing spaces caused field types not to be properly recognized

- fix fields for migration were taken from options passed to resource generator and not the fixed up ones by the resource. ie. int => integer, bool => boolean

- add index view generator for resource generates a foreign model reference name for the foreign id field.

- add `{{field:line}}` to controller generator, expands line for every field in the model. Replacing the placeholder with the field name. The placeholder can appear multiple times on the same line, all instances will be replaced by the field name. Intended use is to generate code needed on a per field basis. 

- add `{{field:line:bool}}` to controller and model generator, expands line only for boolean fields  

- add `{{field:line:nobool}}` to controller and model generator, expands line only for nonboolean fields  

- add `{{relations:line}}` to controller and model generator, expands line only for auto-detected foreign key fields (ones ending in `_id`). The `{{relations:line}}` placeholder is removed, instead `{{relations:modelVar}}` is replaced by the foreign model name (the part before `_id`), where modelVar is one of the model case names in the table below. For example {{relations:snake_model}} will be raplaced by the snake_case foreign model name.  

- add translations generator to scaffolding and resource generator, which will create files in each subdirectory of app/lang with the name {{model}}.php (all lowercase) that will contain an array of `'{{field}}' => '{{field}}',` scaffold for localizing field names.

- fixed resource generator model name case changing to handle camelCase, instead of lower casing the model and then capitalizing it. now blockedEmail will be BlockedEmails instead of Blockedemails.

- resource generator now will add a commented out version of the resource route definition if a resource definition for that resource already exists but does not match the new one.

- add all variations of case and separators to view, model, controller generators which handle camelCaseModel names consistently. Got tired of adding new ones as the need arose.
This applies to `{{relations:modelVar}}` where modelVar is one of the fields below. This is replaced by a quoted, comma separated list of all the foreign relationship models for the current model.

#### Model Vars Table

for a model named `camelCaseModel` the model vars given by the field entry will be replaced with as per table.


| field               | replaced with        | field                | replaced with         |
|:--------------------|:---------------------|:---------------------|:----------------------|
| `{{camelModel}}`    |  `camelCaseModel`    |  `{{CamelModel}}`    |  `CamelCaseModel`     | 
| `{{camelModels}}`   |  `camelCaseModels`   |  `{{CamelModels}}`   |  `CamelCaseModels`    | 
| `{{model}}`         |  `camelcasemodel`    |  `{{dash-model}}`    |  `camel-case-model`   |
| `{{models}}`        |  `camelcasemodels`   |  `{{dash-models}}`   |  `camel-case-models`  |
| `{{Model}}`         |  `CamelCaseModel`    |  `{{Dash-Model}}`    |  `Camel-Case-Model`   |
| `{{Models}}`        |  `CamelCaseModels`   |  `{{Dash-Models}}`   |  `Camel-Case-Models`  |
| `{{MODEL}}`         |  `CAMELCASEMODEL`    |  `{{DASH-MODEL}}`    |  `CAMEL-CASE-MODEL`   |
| `{{MODELS}}`        |  `CAMELCASEMODELS`   |  `{{DASH-MODELS}}`   |  `CAMEL-CASE-MODELS`  |
| `{{snake_model}}`   |  `camel_case_model`  |  `{{space model}}`   |  `camel case model`   |
| `{{snake_models}}`  |  `camel_case_models` |  `{{space models}}`  |  `camel case models`  |
| `{{Snake_Model}}`   |  `Camel_Case_Model`  |  `{{Space Model}}`   |  `Camel Case Model`   |
| `{{Snake_Models}}`  |  `Camel_Case_Models` |  `{{Space Models}}`  |  `Camel Case Models`  |
| `{{SNAKE_MODEL}}`   |  `CAMEL_CASE_MODEL`  |  `{{SPACE MODEL}}`   |  `CAMEL CASE MODEL`   |
| `{{SNAKE_MODELS}}`  |  `CAMEL_CASE_MODELS` |  `{{SPACE MODELS}}`  |  `CAMEL CASE MODELS`  |

### 1.2.6

- fix test generator to handle camel case model names instead of forcing lowercase
- fix migration generator to convert camel case to snake case on model names instead of using camel case
- fix database seeder generator to handle camel case model names instead of forcing lowercase
- if a generated file exists in the project all generators will now create a file with .new appended to the name instead of doing nothing. That way you can change the template or field list, generate a new version and user file diff to merge in the desired changes.

#### Migration Generator

- if a migration file exists that matches generated name except for the date prefix then .new is appended to the name instead of creating a new migration. multiple runs don't create multiple migrations that create the same table.
- auto recognized foreign keys will have `->unsigned()` added to column creation line, and a foreign index on the foreign model.

        $table->foreign('{{field}}')->references('id')->on('{{fnames}}')

    with `{{fnames}}` being the foreign model name (lowercase, plural) and `{{field}}` is the original field name. For a field named user_id it will look like:

    $table->foreign('user_id')->references('id')->on('users')

#### Resource Generator

- resource routes are now added to the `routes.php` file above the line `// Generators:insert new routes here`, it must have no leading or trailing spaces, if not found then the route is appended to the end of the file.
- if a resource route already exists the file is not modified.


#### Model Generator

- add autocorrect on field types:

    int => integer
    bool => boolean
  
- add `{{field:unique}}` to expand to the first model field that had a :unique type option, or `id` if one was not found.
 
- add `{{field:line}}` will repeat the template line(s) containing it for every field in the model replacing the marker with the field name. Useful for generating per field custom code in the template.
  
- add auto recognition of foreign keys based on field type being integer and field name ending in `_id`, the foreign model name is the field name before the `_id` suffix. Now these fields are declared `->unsigned()->unique()` and a foreign key entry is made in the migration. Additionally extra template expansions are recognized in the template file for model.txt and controller.txt:  
  
  `{{relations}}` is expanded to: 
  
    public
    function {{fname}}()
    {
        return $this->belongsTo('{{Fname}}', '{{field}}', 'id');
    }

  with `{{fname}}` being the foreign model name (lowercase) and `{{Fname}}` is the same capitalized, `{{field}}` is the original field name. For a field named user_id it will look like:
  
    public
    function user()
    {
        return $this->belongsTo('User', 'user_id', 'id');
    }

  `{{relations:model}}`, `{{relations:models}}`, `{{relations:Model}}`, `{{relations:Models}}` are expanded to the foreign model name or names correspondingly cased (ie. user, users, User, Users). as a list of strings. Your custom templates you can create functions that have a list of foreign model names. intended use is to include a `->with({{relations:model}})` in the template for eager loading of relationships. 
  
#### View Generator

- add markers to expand in the view templates
    - `{{formElements:readonly}}` same as formElements but all form elements are marked readonly or disabled
    - `{{formElements:nobool}}` all form elements for non-boolean fields (no checkboxes)
    - `{{formElements:nobool:readonly}}` combo of nobool and readonly
    - `{{formElements:bool}}` only checkboxes
    - `{{formElements:bool:readonly}}` only checkboxes, readonly
    - `{{formElements:op}}` form elements whose readonly status is determined by a variable passed to `View::make` named `$op`, if `$op === 'view'` then fields are readonly.
    - `{{formElements:bool:op}}`
    - `{{formElements:nobool:op}}`
    - `{{formElements:filters}}` produce form elements that are selects for filtering the actual elements. These go into the same table as the model data, but all have `form="form-filter"` attribute. So you can place them anywhere in the page.
    - `{{headings:lang}}` produce table headings but wrap the field names in `@lang('messages.{{field}}')` where `{{field}}` is the name of the fields. Use it if you need to localize table header row for different languages.
    - `{{fields:nobuttons}}` produce fields for the table but no buttons for edit, delete. Use if you have your own buttons in the template.

- string fields longer than 64 characters are now mapped to \<textarea\>, except for fields named: email, name, password or password_confirmation.

- integer fields whose name ends in `_id` are assumed to be foreign keys:
    - use \<select\> for their input, with the list of options passed to the `View::make` in `$models` (ie. field named `user_id` will look for its list in `$users` variable.) 
    - Fields generated for filters will have an empty option added for no filtering selection.
    - create button is added with a `URL::route` to `'models.create'` (ie. field named user_id, will have a route to 'users.create'
  
### First mods after fork from wdollar/Laravel-4-Generators-Bootstrap-3.
  
- add a LICENSE file from Jeffery Way's original package

- package changed to Vsch/Generators

- Modified tests to reflect changes to implementation and the EOL that is now at the end of templates 

- add Laravel resource directories, for now only /config is not empty

- add config/generators.php to store the path to the template directory

- move templates directory to config/templates

- Now you can:

    `php artisan config:publish vsch/generators`

To have the `config/` and `config/templates/` directories added under your project's `app/config/packages/vsch/generators` directory. 
For your very own copy of the templates that will not be overwritten by a package update. 
You do not need to modify the `config/generators.php` file unless you want your templates directory somewhere other than the default location.

You only need to keep the template files that you want to modify. Any files not found in your app's `config/packages/.../template` 
directory will fallback to using the package versions.
