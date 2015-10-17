<?php

use Vsch\Generators\Generators\ModelGenerator;
use Mockery as m;

class ModelGeneratorTest extends PHPUnit_Framework_TestCase {
    protected static $templatesDir;

    public function __construct()
    {
        static::$templatesDir = __DIR__.'/../../src/config/templates';
    }

    public function tearDown()
    {
        m::close();
    }

    public function testCanGenerateModelUsingTemplate()
    {
        //$file = m::mock('Illuminate\Filesystem\Filesystem')->makePartial();
        //$cache = m::Mock('Vsch\Generators\Cache');
        //
        //$file->shouldReceive('put')
        //     ->once()
        //     ->with(app_path() . '/models/Foo.php', file_get_contents(__DIR__.'/stubs/model.txt'));
        //
        //$generator = new ModelGenerator($file, $cache);
        //$generator->make(app_path() . '/models/Foo.php', static::$templatesDir.'/model.txt');
    }

    public function testCanGenerateModelUsingCustomTemplateAndNoFields()
    {
        $file = m::mock('Illuminate\Filesystem\Filesystem')->makePartial();
        $cache = m::Mock('Vsch\Generators\Cache');

        $cache->shouldReceive('getFields')
              ->once()
              ->andReturn(false);

        $file->shouldReceive('put')
             ->once()
             ->with(app_path() . '/models/Foo.php', file_get_contents(__DIR__.'/stubs/scaffold/model-no-fields.txt'));

        $generator = new ModelGenerator($file, $cache);
        $generator->make(app_path() . '/models/Foo.php', static::$templatesDir . '/scaffold/model.txt',);
    }

    public function testCanGenerateModelUsingCustomTemplateAndFields()
    {
        $file = m::mock('Illuminate\Filesystem\Filesystem')->makePartial();
        $cache = m::Mock('Vsch\Generators\Cache');

        $cache->shouldReceive('getFields')
              ->once()
              ->andReturn(['title' => 'string', 'age' => 'integer']);

        $cache->shouldReceive('getModelName')
              ->once()
              ->andReturn('foo');

        $file->shouldReceive('put')
             ->once()
             ->with(app_path() . '/models/Foo.php', file_get_contents(__DIR__.'/stubs/scaffold/model.txt'));

        $generator = new ModelGenerator($file, $cache);
        $generator->make(app_path() . '/models/Foo.php', static::$templatesDir . '/scaffold/model.txt',);
    }
}
