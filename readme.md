#### (License MIT)

### Forked from wdollar/Laravel-4-Generators-Bootstrap-3.
### Forked from JeffreyWay/Laravel-4-Generators.

This package had the following modifications to Wes Dollar's package:

At this point I feel that the generators are in desperate need of a rewrite to simplify and harmonize the template syntax, add more control to how code is generated. This is on a todo list.
I am adding cludges just to get the functionality I need for the project I am working on. It is not the best way but the only way on a limited time budget.

### Version 1.3.2

- add `--bench="name/package"` option to all generator commands, generated code will be added to the given name/package in the workbench. A convenient way to scaffold models, controllers and migrations in packages on which you are working in the project workbench directory.

- add `--prefix="prefix_"` option to migration and model generator commands, generated code will add a prefix to the table names of the model. Additionally, with --bench option the prefix will be taken from the package's `config.table_prefix` configuration via `\Config::get('package::config.table_prefix','')`. `--prefix` option has precedence over `--bench`. If non-empty `--prefix` is specified then it will be used instead of the package config setting, for both the model and the migration files.

- add `primary(n,m)` field definition. This is the same as `index(n,m)` and `keyindex(n,m)` but will create a primary index for the fields. See: [index hint](#IndexHint)

- add `{{relation:line}}` to model generator, it will repeat the line containing this tag for every foreign relation of the model, while replacing `{{relation:var_name}}` where `var_name` is one of the field names from the [Model Vars Table](#ModelVarsTable) table below. For example:

lines in model.txt:

    public static $remote_relations = array(
        {{relation:line}}'{{relation:snake_model}}'=>['{{relation:snake_model}}_id', 'id'],
    );

for a model that has two foreign key fields: `sender_id` and `conversation_id` will have the following resulting code: 

    public static $remote_relations = array(
        'sender'=>['sender_id', 'id'],
        'conversation'=>['conversation_id', 'id'],
    );

for a model that has no foreign key fields the line is omitted:

    public static $remote_relations = array(
    );

### Version 1.3.1

- rewrote field string parsing to handle nested (),[] and {}. Now field options can have comma separated parameters. 

- add default(..) hint now adds to the `{{defaults}}` placeholder. Use in the model.txt template to create an associative array of field name to default value. This can be used to provide defaults to the form when creating and also to fill in default values and replace empty strings by nulls for numeric and date/datetime fields. More docs on the subject are in the future.

- add rule(....) hint. Adds the stuff between parentheses to the field's rules. Some rules are added automatically. Numeric fields get `numeric`, if the field is not nullable then it gets `required`. email will get `email|unique|table:email,id,{id}` added, the `{id}` placeholder should be changed to the id of the model when it is being saved to prevent triggering a unique e-mail validation failure.
- add <a name="IndexHint"></a>index hint: index(indexdef) to create an index and keyindex(indexdef) to create a unique index, in the migration file. indexdef has the format: i,n, where i and n are integers, i is the index id, n is the position of the field in the index. If n is not given then the position will be the order of the field's appearance. Used to automatically add indices to table creation script in migration file. Multiple fields can specify the same index id and can have multiple index hints with different ids. 


    user_name:string[24]:keyindex(1,1), city:string[32]:index(2,2), state:string[32]:index(2,1) 

will create a table with two indices: unique index on user_name and a non-unique index on state,city.

- doc forgot to document new field types being handled in version 1.3.0 and added hints to model fields. Hints are used by the generators but stripped out of the migration file.
hints are added as options after the field type with additional `:` separating them. Any options that do not have a `(` get () added on. This was pre-existing functionality. 
Additionally, the generators recognize and use standard options: nullable, default to affect the generated code. Some shortcut names are added to reduce typing

| shortcut type | laravel type |
| :---- |:------ |
| int		| integer |
| tinyint	| tinyInteger |
| smallint	| smallInteger |
| medint	| mediumInteger |
| mediumint	| mediumInteger |
| bigint	| bigInteger |
| bool		| boolean |
| datetime	| dateTime |
| decimal	| decimal, except specify the parameter as n.m instead of n,m, ie. decimal(6,2) should be given as decimal\[6.2\] |


| type(s) | effect in code |
| :---- |:------ |
| date, dateTime    | wraps the field in a div.form-group.date, adds a span.input-group-addon with a calendar glyphicon. If you include bootstrap-datepicker then this gives a pop-up calendar for the field  |
| all integer types | generates a number field in views, int and bigint are treated as foreign keys if they have an \_id suffix in the field name | 
| boolean | generates a checkbox field in views, also used for :bool and :nobool expansion placeholders | 


| hint | effect |
| :---- |:------ |
| hidden     | adds the field name to the `{{hidden}}` fields list placeholder |
| guarded   | adds the field name to the `{{guarded}}` fields list placeholder |
| notrail   | adds the field name to the `{{notrail}}` fields list placeholder |
| notrailonly   | adds the field name to the `{{notrailonly}}` fields list placeholder |
| textarea  | uses textarea for the field in the view generator instead of text |
| index(indexdef) | creates a non-unique index (index) which includes the field, see notes for indexdef format |
| keyindex  | creates a unique index which includes the field, see notes for indexdef format |

Usage:  *`field_name`*`:int:hidden:guarded`, or *`field_name`*`:string[256]:textarea:notrail`

### Version 1.3.0

#### These are specific to my use and will not work without adding some support code

- change view generator -- `{{formElements:op}}` to generate elements as readonly or disabled (for checkboxes) based on a variable $op wrapped in isViewOp($op) function. That way the code that determines when the fields should be readonly can be isolated from the generator.

- change view generator for admin.txt generates three types of fields for foreign keys: select and text. The select is populated from a variable based on the camelCaseModels foreign name. The text field and a hidden field are intended to be used together. The hidden field hold the id of the foreign model and the text field a human readable form. I use the typeahead jQuery plugin, with some server code, to resolve the human readable field to the id exected by the database. 

#### These are fairly generic

- fix trailing spaces caused field types not to be properly recognized

- fix fields for migration were taken from options passed to resource generator and not the fixed up ones by the resource. ie. int => integer, bool => boolean

- add index view generator for resource generates a foreign model reference name for the foreign id field.

- add `{{field:line}}` to controller generator, expands line for every field in the model. Replacing the placeholder with the field name. The placeholder can appear multiple times on the same line, all instances will be replaced by the field name. Intended use is to generate code needed on a per field basis. 

- add `{{field:line:bool}}` to controller and model generator, expands line only for boolean fields  

- add `{{field:line:nobool}}` to controller and model generator, expands line only for nonboolean fields  

- add `{{relations:line}}` to controller and model generator, expands line only for auto-detected foreign key fields (ones ending in `_id`). The `{{relations:line}}` placeholder is removed, instead `{{relations:modelVar}}` is replaced by the foreign model name (the part before `_id`), where modelVar is one of the model case names in the table below. For example {{relations:snake_model}} will be raplaced by the snake_case foreign model name.  

- add translations generator to scaffolding and resource generator, which will create files in each subdirectory of app/lang with the name {{model}}.php (all lowercase) that will contain an array of `'{{field}}' => '{{field}}',` scaffold for localizing field names.

- fixed resource generator model name case changing to handle camelCase, instead of lowercasing the model and then capitalizing it. now blockedEmail will be BlockedEmails instead of Blockedemails.

- resource generator now will add a commented out version of the resource route definition if a resource definition for that resource already exists but does not match the new one.

- add all variations of case and separators to view, model, controller generators which handle camelCaseModel names consistently. Got tired of adding new ones as the need arose.
This applies to `{{relations:modelVar}}` where modelVar is one of the fields below. This is replaced by a quoted, comma separated list of all the foreign relationship models for the current model.

#### <a name="ModelVarsTable"></a>Model Vars Table

for a model named `camelCaseModel` the model vars given by the field entry will be replaced with as per table.


| field | replaced with | field | replaced with |
| :---- |:------ | :---- |:------ |
| {{camelModel}}    |      camelCaseModel    | {{CamelModel}}    |      CamelCaseModel    | 
| {{camelModels}}   |      camelCaseModels   | {{CamelModels}}   |      CamelCaseModels   | 
| {{model}}         |      camelcasemodel    | {{dash-model}}    |      camel-case-model  |
| {{models}}        |      camelcasemodels   | {{dash-models}}   |      camel-case-models |
| {{Model}}         |      CamelCaseModel    | {{Dash-Model}}    |      Camel-Case-Model  |
| {{Models}}        |      CamelCaseModels   | {{Dash-Models}}   |      Camel-Case-Models |
| {{MODEL}}         |      CAMELCASEMODEL    | {{DASH-MODEL}}    |      CAMEL-CASE-MODEL  |
| {{MODELS}}        |      CAMELCASEMODELS   | {{DASH-MODELS}}   |      CAMEL-CASE-MODELS |
| {{snake_model}}   |      camel_case_model  | {{space model}}   |      camel case model  |
| {{snake_models}}  |      camel_case_models | {{space models}}  |      camel case models |
| {{Snake_Model}}   |      Camel_Case_Model  | {{Space Model}}   |      Camel Case Model  |
| {{Snake_Models}}  |      Camel_Case_Models | {{Space Models}}  |      Camel Case Models |
| {{SNAKE_MODEL}}   |      CAMEL_CASE_MODEL  | {{SPACE MODEL}}   |      CAMEL CASE MODEL  |
| {{SNAKE_MODELS}}  |      CAMEL_CASE_MODELS | {{SPACE MODELS}}  |      CAMEL CASE MODELS |

### Version 1.2.6

- fix test generator to handle camel case model names instead of forcing lowercase
- fix migration generator to convert camel case to snake case on model names instead of using camel case
- fix database seeder generator to handle camel case model names instead of forcing lowercase
- if a generated file exists in the project all generators will now create a file with .new appended to the name instead of doing nothing. That way you can change the template or field list, generate a new version and user file diff to merge in the desired changes.

#### Migration Generator

- if a migration file exists that matches generated name except for the date prefix then .new is appended to the name instead of creating a new migration. multiple runs don't create multiple migrations that create the same table.
- auto recognized foreign keys will have `->unsigned()` added to column creation line, and a foreign index on the foreign model.

    `$table->foreign('{{field}}')->references('id')->on('{{fnames}}')`

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
-- `{{formElements:readonly}}` same as formElements but all form elements are marked readonly or disabled
-- `{{formElements:nobool}}` all form elements for non-boolean fields (no checkboxes)
-- `{{formElements:nobool:readonly}}` combo of nobool and readonly
-- `{{formElements:bool}}` only checkboxes
-- `{{formElements:bool:readonly}}` only checkboxes, readonly
-- `{{formElements:op}}` form elements whose readonly status is determined by a variable passed to `View::make` named `$op`, if `$op === 'view'` then fields are readonly.
-- `{{formElements:bool:op}}`
-- `{{formElements:nobool:op}}`
-- `{{formElements:filters}}` produce form elements that are selects for filtering the actual elements. These go into the same table as the model data, but all have `form="form-filter"` attribute. So you can place them anywhere in the page.
-- `{{headings:lang}}` produce table headings but wrap the field names in `@lang('messages.{{field}}')` where `{{field}}` is the name of the fields. Use it if you need to localize table header row for different languages.
-- `{{fields:nobuttons}}` produce fields for the table but no buttons for edit, delete. Use if you have your own buttons in the template.

- string fields longer than 64 characters are now mapped to \<textarea\>, except for fields named: email, name, password or password_confirmation.

- integer fields whose name ends in `_id` are assumed to be foreign keys:
-- use \<select\> for their input, with the list of options passed to the `View::make` in `$models` (ie. field named `user_id` will look for its list in `$users` variable.) 
-- Fields generated for filters will have an empty option added for no filtering selection.
-- create button is added with a `URL::route` to `'models.create'` (ie. field named user_id, will have a route to 'users.create'
  
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

# Development caveats: 
- I am currently working on this package in my project's workbench directory and not as a full fledged package. 
- Tested by deploying with composer. That was the extent of my package installation and testing other than unit tests in the package and using it on my project.
- This package is provided as is. I will do my best to make sure it is in working order but use it at your own discretion.

# Package docs

This package updates the views provided by Jeffrey Way's original works to Bootstrap 3. The original documentation from Way's works follows. The instructions have been updated to reflect any changes within this package.

This Laravel 4 package provides a variety of generators to speed up your development process. These generators include:

- `generate:model`
- `generate:controller`
- `generate:seed`
- `generate:view`
- `generate:migration`
- `generate:resource`
- `generate:scaffold`
- `generate:form`
- `generate:test`
- `generate:pivot` <-- NEW!!

## Prefer a Video Walk-through?

[See here.](http://tutsplus.s3.amazonaws.com/tutspremium/courses_$folder$/WhatsNewInLaravel4/9-Generators.mp4)

## Installation

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `vsch/generators`.

	"require": {
		"laravel/framework": "4.0.*",
		"vsch/generators": "dev-master"
	},
	"minimum-stability" : "dev"

Next, update Composer from the Terminal:

    composer update

Once this operation completes, the final step is to add the service provider. Open `app/config/app.php`, and add a new item to the providers array.

    'Vsch\Generators\GeneratorsServiceProvider'

That's it! You're all set to go. Run the `artisan` command from the Terminal to see the new `generate` commands.

    php artisan

> There's also a [Sublime Text plugin available](http://net.tutsplus.com/tutorials/tools-and-tips/pro-workflow-in-laravel-and-sublime-text/) to assist with the generators. Definitely use it, but not before you learn the syntax below.

If you want a copy of the templates in your project's directory so that you can modify them, run the following command from the Terminal in your project directory:

    php artisan config:publish vsch/generators
    
This will copy the /config/generate.php and /config/templates/ subdirectory to your project's config/:

    config/
        packages/
            vsch/
                generators/
                    generators.php
                    templates/

You only need to keep the template files that you want to modify. Any files not found in your app's config/packages/.../template directory will fallback to using the package versions.

## Usage

Think of generators as an easy way to speed up your workflow. Rather than opening the models directory, creating a new file, saving it, and adding the class, you can simply run a single generate command.

- [Migrations](#migrations)
- [Models](#models)
- [Views](#views)
- [Seeds](#seeds)
- [Resources](#resources)
- [Scaffolding](#scaffolding)
- [Forms](#forms)
- [Tests](#tests)
- [Pivot Tables](#pivot-tables)

### Migrations

Laravel 4 offers a migration generator, but it stops just short of creating the schema (or the fields for the table). Let's review a couple examples, using `generate:migration`.

    php artisan generate:migration create_posts_table

If we don't specify the `fields` option, the following file will be created within `app/database/migrations`.

```php
<?php

use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration {

    /**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	  Schema::create('posts', function($table)
	  {
	    $table->increments('id');

	    $table->timestamps();
	  });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
	  Schema::drop('posts');
	}

}
```

Notice that the generator is smart enough to detect that you're trying to create a table. When naming your migrations, make them as description as possible. The migration generator will detect the first word in your migration name and do its best to determine how to proceed. As such, for `create_posts_table`, the keyword is "create," which means that we should prepare the necessary schema to create a table.

If you instead use a migration name along the lines of `add_user_id_to_posts_table`, in that case, the keyword is "add," signaling that we intend to add rows to an existing table. Let's see what that generates.

    php artisan generate:migration add_user_id_to_posts_table

This will prepare the following boilerplate:

```php
<?php

use Illuminate\Database\Migrations\Migration;

class AddUserIdToPostsTable extends Migration {

    /**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	  Schema::table('posts', function($table)
	  {

	  });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
	  Schema::table('posts', function($table)
	  {

	  });
	}

}
```

Notice how, this time, we're not doing `Schema::create`.

#### Keywords

When writing migration names, use the following keywords to provide hints for the generator.

- `create` or `make` (`create_users_table`)
- `add` or `insert` (`add_user_id_to_posts_table`)
- `remove` or `drop` or `delete` (`remove_user_id_from_posts_table`)

#### Generating Schema

This is pretty nice, but let's take things a step further and also generate the schema, using the `fields` option.

    php artisan generate:migration create_posts_table --fields="title:string, body:text"

Before we decipher this new option, let's see the output:

```php
<?php

use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration {

    /**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	  Schema::create('posts', function($table)
	  {
	    $table->increments('id');
	    $table->string('title');
	    $table->text('body');
	    $table->timestamps();
	  });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
	  Schema::drop('posts');
	}

}
```

Nice! A few things to notice here:

- The generator will automatically set the `id` as the primary key.
- It also will add the timestamps, as that's more common than not.
- It parsed the `fields` options, and added those fields.
- The drop method is smart enough to realize that, in reverse, the table should be dropped entirely.

To declare fields, use a comma-separated list of key:value:option sets, where `key` is the name of the field, `value` is the [column type](http://four.laravel.com/docs/schema#adding-columns), and `option` is a way to specify indexes and such, like `unique` or `nullable`. Here are some examples:

- `--fields="first:string, last:string"`
- `--fields="age:integer, yob:date"`
- `--fields="username:string:unique, age:integer:nullable"`
- `--fields="name:string:default('John'), email:string:unique:nullable"`
- `--fields="username:string[30]:unique, age:integer:nullable"`

Please make note of the last example, where we specify a character limit: `string[30]`. This will produce `$table->string('username', 30)->unique();`

It is possible to destroy the table by issuing:

	php artisan generate:migration destroy_posts_table
	
If you'd like to have an accurate artisan rollback option set the `fields` option as well:

	php artisan generate:migration destroy_posts_table --fields="title:string, body:text"

As a final demonstration, let's run a migration to remove the `completed` field from a `tasks` table.

    php artisan generate:migration remove_completed_from_tasks_table --fields="completed:boolean"

This time, as we're using the "remove" keyword, the generator understands that it should drop a column, and add it back in the `down()` method.

```php
<?php

use Illuminate\Database\Migrations\Migration;

class RemoveCompletedFromTasksTable extends Migration {

    /**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
	  Schema::table('tasks', function($table)
	  {
	    $table->dropColumn('completed');
	  });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
	  Schema::table('tasks', function($table)
	  {
	    $table->boolean('completed');
	  });
	}

}
```

### Models

    php artisan generate:model Post

This will create the file, `app/models/Post.php` and insert the following boilerplate:

```php
<?php

class Post extends Eloquent {

}
```

### Views

    php artisan generate:view dog

This command will generate `app/views/dog.blade.php` and a simple string, for convenience.

    The dog.blade.php view.

As with all of the commands, you may specify a `--path` option to place this file elsewhere.

    php artisan generate:view index --path=views/dogs

Now, we get: `app/views/dogs/index.blade.php`.

### Seeds

Laravel 4 provides us with a flexible way to seed new tables.

    php artisan generate:seed dogs

Set the argument to the name of the table that you'd like a seed file for. This will generate `app/database/seeds/DogsTableSeeder.php` and populate it with:

```php
<?php

class DogsTableSeeder extends Seeder {

  public function run()
  {
    $dogs = [

    ];

    DB::table('Dogs')->insert($dogs);
  }

}
```

This command will also update `app/database/seeds/DatabaseSeeder.php` to include a call to this new seed class, as required by Laravel.

To fully seed the `dogs` table:

- Within the `$dogs` array, add any number of arrays, containing the necessary rows.
- Return to the Terminal and run Laravel's `db:seed command` (`php artisan db:seed`).

### Resources

Think of the resource generator as the big enchilada. It calls all of its sibling generate commands. Assuming the following command:

    php artisan generate:resource dog --fields="name:string"

The following actions will take place:

- Creates a `create_dogs_table` migration, with a name column.
- Creates a `Dog.php` model.
- Creates a `views/dogs` folder, containing the `index`, `show`, `create`, and `edit` views.
- Creates a `database/seeds/DogsTableSeeder.php` seed file.
- Updates `DatabaseSeeder.php` to run `DogsTableSeeder`
- Creates `controllers/DogsController.php`, and fills it with restful methods.
- Updates `routes.php` to include: `Route::resource('dogs', 'DogsController')`.
- Creates a `tests/controllers/DogsControllerTest.php` file, and fills it with some boilerplate tests to get you started.

> Please note that the resource name is singular - the same as how you would name your model.

#### Workflow

Let's create a resource for displaying dogs in a restful way.

    php artisan generate:resource dog --fields="name:string, age:integer"

Next, we'll seed this new `dogs` table. Open `database/seeds/DogsTableSeeder.php` and add a couple of rows. Remember, you only need to edit the `$dogs` array within this file.

    $dogs = [
        ['name' => 'Sparky', 'age' => 5],
        ['name' => 'Joe', 'age' => 11]
    ];

Now, we migrate the database and seed the `dogs` table.

    php artisan migrate
    php artisan db:seed

Finally, let's display these two dogs, when accessing the `dogs/` route. Edit `controllers/DogsController.php`, and update the `index` method, like so:

    public function index()
    {
        return View::make('dogs.index')
    		->with('dogs', Dog::all());
    }

The last step is to update the view to display each of the posts that was passed to it. Open `views/dogs/index.blade.php` and add:

    <ul>
        @foreach($dogs as $dog)
    		<li>{{ $dog->name }} : {{ $dog->age }}</li>
    	@endforeach
    </ul>

Okay, okay, we're not using a layout file with the proper HTML. Who cares; this is just an example, fool.

Anyhow, we're all set. Run the server, and browse to `localhost:8000/dogs` to view your list.

    php artisan serve

- Sparky : 5
- Joe : 11

Isn't that way faster than manually doing all of that writing? To finish up, let's run the tests to make sure that everything is working, as expected.

    phpunit

And...it's green!

### Scaffolding

![scaffolding](https://dl.dropboxusercontent.com/u/774859/GitHub-Repos/scaffold-example.png)

Think of scaffolding as an extension of a resource. It has the exact same interface.

```bash
php artisan generate:scaffold tweet --fields="author:string, body:text"
```

The only difference is that it will handle all of the boilerplate. This can be particularly useful for prototyping - or even learning how to do basic things, such as delete a record from a database table, or build a form, or perform validation on that form.

![view scaffold](https://dl.dropboxusercontent.com/u/774859/GitHub-Repos/scaffold-view.png)

![view validation](https://dl.dropboxusercontent.com/u/774859/GitHub-Repos/scaffold-validation.png)

### Forms
This handy new generator allows you to, with a single command, generate the necessary HTML for a form, based on attributes from a provided model. Perhaps an example is in order:

```bash
php artisan generate:form tweet
```
Assuming that I do have a `Tweet` model and its associated `tweet` table, this command will output:

```html
{{ Form::open(array('route' => 'tweets.store')) }}
    <ul>
        <li>
            {{ Form::label('author', 'Author:') }}
            {{ Form::text('author') }}
        </li>

        <li>
            {{ Form::label('body', 'Body:') }}
            {{ Form::textarea('body') }}
        </li>

        <li>
            {{ Form::submit() }}
        </li>
    </ul>
{{ Form::close() }}
```
Pretty neat, huh? It read the attributes and data types, and prepared the markup for you! One less thing to worry about!

#### Specifying the Form's Method
But what if you intend to update a resource, rather than create a new one? Well, in that case, use the `--method` option.

```bash
php artisan generate:form tweet --method="update"
```

This will mostly generate the same HTML, however, the `Form::open()` method will be adjusted, as needed:

```php
{{ Form::open(array('method' => 'PATCH', 'route' => 'tweets.update')) }}
```

The method option will accept any number of values (*add, edit, update, post, create, etc.*), but, essentially, you're just telling it whether you are creating or editing a resource. As such, there's only two possible outputs: `POST` and `PATCH` (the former being the default).

#### Custom HTML

What if you don't like the idea of using an unordered list for a form? Use the `--html` option, along with the name of the element that you'd prefer to use:

```bash
php artisan generate:form tweet --html="div"
```
Now, the generator we'll present the elements within `div`s!

```html
{{ Form::open(array('route' => 'tweets.store')) }}
    <div>
        {{ Form::label('author', 'Author:') }}
        {{ Form::text('author') }}
    </div>

    <div>
        {{ Form::label('body', 'Body:') }}
        {{ Form::textarea('body') }}
    </div>

    <div>
        {{ Form::submit() }}
    </div>
{{ Form::close() }}
```

#### Copying and Saving

At least for now, and unlike the other generators in this package, this command will output the form, at which point you can copy and paste it where needed. Of course, you can always pipe the output to the clipboard or save to a file, using existing tools. For instance:

```bash
# copy the output to the clipboard
php artisan generate:form tweet | pbcopy

# save it to a form partial
php artisan generate:form tweet > app/views/posts/form.blade.php
```
### Tests

Use `generate:test` when you need to create a new PHPUnit test class. Here's an example:

```bash
php artisan generate:test FooTest
```

This will produce `app/tests/FooTest.php`.

```php
<?php

class FooTest extends TestCase {

    public function test()
    {

    }

}
```

### Pivot Tables

Creating joinable/pivot tables can sometimes be confusing.

- Should the table names be plural?
- In what order do we write the table names to make Laravel happy?
- What fields should be in the pivot table?

This process can be automated now. Simply call the `generate:pivot`
command, and provide the names of the tables that should be joinable.
For example, a post can have many tags, and a tag can have many posts.
Run the following command to create the necessary pivot table.

```bash
php artisan generate:pivot posts tags
```

It doesn't matter which order you provide the table names (or whether
you pluralize them or not). The command will correctly create a
`post_tag` migration that has `post_id` and `tag_id` fields.

```php
Schema::create('post_tag', function(Blueprint $table) {
    $table->integer('post_id');
    $table->integer('tag_id');
});
```


Finally, simply migrate the database to create it.

```bash
php artisan migrate
```

Pivot table finished!

To put it all together, let's do it from scratch. We need a posts table,
a tags table, and the connecting pivot table for the two. We can tackle
this easily with the generators.

```bash
php artisan generate:migration create_posts_table --fields="title:string, description:text"

php artisan generate:migration create_tags_table --fields="name:string"

php artisan generate:pivot posts tags
```

