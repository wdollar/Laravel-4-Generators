<?php

use Vsch\Generators\Commands\ModelGeneratorCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Mockery as m;

class ModelGeneratorCommandTest extends PHPUnit_Framework_TestCase {
    public function tearDown()
    {
        m::close();
    }

    public function testGeneratesModelSuccessfully()
    {
        $gen = m::mock('Vsch\Generators\Generators\ModelGenerator');

        $gen->shouldReceive('make')
            ->once()
            ->with(app_path() . '/models/Foo.php', m::any())
            ->andReturn(true);

        $command = new ModelGeneratorCommand($gen);

        $tester = new CommandTester($command);
        $tester->execute(['name' => 'foo']);

        $this->assertEquals("Created " . app_path() . "/models/Foo.php\n", $tester->getDisplay());
    }

    public function testAlertsUserIfModelGenerationFails()
    {
        $gen = m::mock('Vsch\Generators\Generators\ModelGenerator');

        $gen->shouldReceive('make')
            ->once()
            ->with(app_path() . '/models/Foo.php', m::any())
            ->andReturn(false);

        $command = new ModelGeneratorCommand($gen);

        $tester = new CommandTester($command);
        $tester->execute(['name' => 'Foo']);

        $this->assertEquals("Could not create file, instead created " . app_path() . "/models/Foo.php.new\n", $tester->getDisplay());
    }

    public function testCanAcceptCustomPathToModelsDirectory()
    {
        $gen = m::mock('Vsch\Generators\Generators\ModelGenerator');

        $gen->shouldReceive('make')
            ->once()
            ->with(app_path() . '/foo/models/Foo.php', m::any());

        $command = new ModelGeneratorCommand($gen);

        $tester = new CommandTester($command);
        $tester->execute(['name' => 'foo', '--path' => app_path() . '/foo/models']);
    }
}
