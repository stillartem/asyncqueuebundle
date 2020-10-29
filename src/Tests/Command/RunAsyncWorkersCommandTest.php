<?php


namespace Drivenow\AsyncWorkersBundle\Tests\Command;


use Drivenow\AsyncWorkersBundle\Command\RunAsyncWorkersCommand;
use Drivenow\AsyncWorkersBundle\Tests\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Tests\Input\InputArgumentTest;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RunAsyncWorkersCommandTest extends TestCase
{
    /** @var Application */
    private $application;

    /** @var ContainerInterface */
    private $container;

    /** @var RunAsyncWorkersCommand */
    private $commandObject;

    /** @var RunAsyncWorkersCommand */
    private $command;


    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->container = $this->getContainer();

        $this->application = new Application(self::$kernel);
        $this->application->add(new RunAsyncWorkersCommand());

        $this->commandObject = new RunAsyncWorkersCommand();
        $this->commandObject->setContainer($this->container);

        $this->command = $this->application->find('metropolis:run-worker-async');

    }


    public function testRunWorkersCommand_without_worker()
    {
        $commandTester = new CommandTester($this->command);
        $this->expectException(\RuntimeException::class);
        $commandTester->execute(
            [
                'command' => $this->command->getName(),
            ]
        );

    }


    public function testRunWorkersCommand_without__correct_worker()
    {
        $commandTester = new CommandTester($this->command);
        $this->expectException(\RuntimeException::class);
        $commandTester->execute(
            [
                'command'  => $this->command->getName(),
                '--worker' => 'wrong_worker',
            ]
        );

    }


    public function testRunWorkersCommand_OK()
    {
        $commandTester = new CommandTester($this->command);
        $statusCode = $commandTester->execute(
            [
                'command'      => $this->command->getName(),
                '--worker'     => 'example2',
                '--iterations' => 3,
            ]
        );
        $this->assertEquals($statusCode, 0);

    }


    /**
     * @test
     * @dataProvider memorySize
     *
     * @param string $actual
     * @param  int   $expected
     *
     * @throws \ReflectionException
     */
    public function testConvertToBytes($actual, $expected)
    {
        $convertedValue = $this->invokeMethod($this->commandObject, 'convertToBytes', [$actual]);
        $this->assertEquals($expected, $convertedValue);
    }


    /**
     * @test
     * @dataProvider commandInput
     *
     * @param array $actual
     * @param array $expected
     *
     * @throws \ReflectionException
     */
    public function testRetrieveArguments($actual, $expected)
    {
        $definition = $this->getDefinition();

        $input = new ArrayInput($actual, $definition);
        $retrievedArgs_cli = $this->invokeMethod($this->commandObject, 'retrieveArguments', [$input]);

        $this->assertEquals($expected, $retrievedArgs_cli);
    }


    /**
     * @return array
     */
    public function memorySize()
    {
        return [
            [
                '1KB',
                1024,
            ],
            [
                '1MB',
                1024 ** 2,
            ],
            [
                '1GB',
                1024 ** 3,
            ],
            [
                '300',
                300,
            ],
        ];
    }


    /**
     * @return array
     */
    public function commandInput()
    {
        return [
            [
                [
                    'command'               => 'metropolis:run-worker-async',
                    '--worker'              => 'example2',
                    '--max-execution-time'  => '1 day',
                    '--per-select'          => 80,
                    '--iterations'          => 3,
                    '--max-memory-usage'    => '100MB',
                    '--timeout-per-seconds' => 2,
                ],
                [
                    'service'             => 'example_worker_service',
                    'max-execution-time'  => '1 day',
                    'per-select'          => 80,
                    'iterations'          => 3,
                    'max-memory-usage'    => '100MB',
                    'timeout-per-seconds' => 2,
                ],
            ],
            [
                [
                    'command'               => 'metropolis:run-worker-async',
                    '--worker'              => 'example3',
                    '--max-execution-time'  => '1 day',
                    '--per-select'          => 4,
                    '--iterations'          => 3,
                    '--max-memory-usage'    => '1KB',
                    '--timeout-per-seconds' => 2,
                ],
                [
                    'service'             => 'example_worker_service',
                    'max-execution-time'  => '1 day',
                    'per-select'          => 4,
                    'iterations'          => 3,
                    'max-memory-usage'    => '1KB',
                    'timeout-per-seconds' => 2,
                ],
            ],
        ];
    }


    /**
     * @return InputDefinition
     */
    private function getDefinition()
    {
        $definitionArray = [
            '--worker'              => new InputOption('worker'),
            '--max-execution-time'  => new InputOption('max-execution-time'),
            '--iterations'          => new InputOption('iterations'),
            '--max-memory-usage'    => new InputOption('max-memory-usage'),
            '--per-select'          => new InputOption('per-select'),
            '--timeout-per-seconds' => new InputOption('timeout-per-seconds'),
            'command'               => new InputArgument('command'),
        ];

        return new InputDefinition($definitionArray);
    }
}