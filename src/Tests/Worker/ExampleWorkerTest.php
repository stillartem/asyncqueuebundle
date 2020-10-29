<?php


namespace Drivenow\AsyncWorkersBundle\Tests\Worker;


use Drivenow\AsyncWorkersBundle\Exception\MaxAllowedMemoryException;
use Drivenow\AsyncWorkersBundle\Exception\MaxExecutionTimeException;
use Drivenow\AsyncWorkersBundle\Exception\PostponedException;
use Drivenow\AsyncWorkersBundle\Repository\ExampleTaskRepository;
use Drivenow\AsyncWorkersBundle\Tests\TestCase;
use Drivenow\AsyncWorkersBundle\Worker\ExampleWorker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Console\Style\OutputStyle;

class ExampleWorkerTest extends TestCase
{
    /** @var ExampleWorker|\PHPUnit_Framework_MockObject_MockObject */
    private $exampleWorker;

    /** @var ContainerInterface */
    private $container;

    /** @var ExampleTaskRepository */
    private $exampleRepository;

    /** @var OutputStyle */
    private $output;


    protected function setUp()
    {
        $this->container = $this->getContainer();
        $this->exampleRepository = $this->container->get('drivenow_async_worker.repository.rabbitmq_worker_repository');
        $this->exampleWorker = $this->getMockBuilder(ExampleWorker::class)
            ->setConstructorArgs([$this->exampleRepository])
            ->setMethods(['run', 'lock', 'handle'])
            ->getMock();
        $this->output = $this->getMockBuilder(OutputStyle::class)
            ->disableOriginalConstructor()
            ->getMock();
    }


    public function testExecute_thatRunIsCalling()
    {
        $this->exampleWorker->setCycles(1);
        $this->exampleWorker->setMemoryLimit(1024 ** 3);
        $this->exampleWorker->setExecutionTimeLimit('30 sec');
        $this->exampleWorker
            ->expects($this->once())
            ->method('run');
        $this->exampleWorker->execute($this->output);
    }


    public function testExecute_thatMemoryLimitHasReached()
    {
        $this->exampleWorker->setCycles(1);
        $this->exampleWorker->setMemoryLimit(1);
        $this->exampleWorker->setExecutionTimeLimit('30 sec');
        $this->expectException(MaxAllowedMemoryException::class);
        $this->exampleWorker->execute($this->output);

    }

    public function testExecute_thatTimeoutHasReached()
    {
        $this->exampleWorker->setCycles(1);
        $this->exampleWorker->setMemoryLimit(1024 ** 3);
        $this->exampleWorker->setExecutionTimeLimit('0 sec');
        $this->expectException(MaxExecutionTimeException::class);
        $this->exampleWorker->execute($this->output);

    }


    public function testRun_OK()
    {
        /** @var ExampleWorker|\PHPUnit_Framework_MockObject_MockObject $mockWorker */
        $mockWorker = $this->getMockBuilder(ExampleWorker::class)
            ->setConstructorArgs([$this->exampleRepository])
            ->setMethods(['lock', 'handle', 'markAsDone'])
            ->getMock();
        $mockWorker->setCycles(1);
        $mockWorker->setMemoryLimit(1024 ** 3);
        $mockWorker->setExecutionTimeLimit('30 sec');
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('lock')
            ->will($this->returnValue(1));
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('handle');
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('markAsDone');
        $mockWorker->run();
    }


    public function testRun_ERROR()
    {
        /** @var ExampleWorker|\PHPUnit_Framework_MockObject_MockObject $mockWorker */
        $mockWorker = $this->getMockBuilder(ExampleWorker::class)
            ->setConstructorArgs([$this->exampleRepository])
            ->setMethods(['lock', 'handle', 'markAsError'])
            ->getMock();
        $mockWorker->setCycles(1);
        $mockWorker->setMemoryLimit(1024 ** 3);
        $mockWorker->setExecutionTimeLimit('30 sec');
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('lock')
            ->will($this->returnValue(1));
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('handle')
            ->will($this->throwException(new \Exception('test exception')));
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('markAsError');
        $mockWorker->run();
    }


    public function testRun_POSTPONED()
    {
        /** @var ExampleWorker|\PHPUnit_Framework_MockObject_MockObject $mockWorker */
        $mockWorker = $this->getMockBuilder(ExampleWorker::class)
            ->setConstructorArgs([$this->exampleRepository])
            ->setMethods(['lock', 'handle', 'markAsPostponed'])
            ->getMock();
        $mockWorker->setCycles(1);
        $mockWorker->setMemoryLimit(1024 ** 3);
        $mockWorker->setExecutionTimeLimit('30 sec');
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('lock')
            ->will($this->returnValue(1));
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('handle')
            ->will($this->throwException(new PostponedException(new \DateTime())));
        $mockWorker
            ->expects($this->atLeast(1))
            ->method('markAsPostponed');
        $mockWorker->run();
    }
}