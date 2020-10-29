<?php

namespace Drivenow\AsyncWorkersBundle\Repository;


use Drivenow\AsyncWorkersBundle\Exception\PostponedException;
use Drivenow\AsyncWorkersBundle\Model\ExampleTaskEntity;
use Drivenow\AsyncWorkersBundle\Model\TaskEntityAbstract;
use Drivenow\AsyncWorkersBundle\Model\TaskStatus;
use Psr\Log\LoggerInterface;

class ExampleTaskRepository implements TaskRepositoryInterface
{
    /** @var  TaskEntityAbstract[] */
    private $tasks;

    /* @var LoggerInterface */
    private $logger;


    /**
     * ExampleTaskRepository constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        for ($i = 0; $i < 3; $i++) {
            $this->tasks[] = new ExampleTaskEntity();
        }
    }


    /**
     * @param TaskEntityAbstract $task
     * @param \Exception         $exception
     */
    public function markAsError(TaskEntityAbstract $task,\Throwable $exception)
    {
        $task->setStatus(TaskStatus::ERROR);
        $this->save($task);
    }


    /**
     * @param TaskEntityAbstract $task
     */
    public function markAsDone(TaskEntityAbstract $task)
    {
        $task->setStatus(TaskStatus::DONE);
        $this->save($task);
    }


    /**
     * @param TaskEntityAbstract $task
     * @param PostponedException $exception
     */
    public function markAsPostponed(TaskEntityAbstract $task, PostponedException $exception)
    {
        $nextExecTime = $exception->getNextExecTime();
        $task->setNextExecTime($nextExecTime);
        $task->setStatus(TaskStatus::INPROGRESS);
        $this->save($task);
    }


    /**
     * @param int $limit
     * @param int $shardMax
     * @param int $shardNum
     *
     * @return \Drivenow\AsyncWorkersBundle\Model\TaskEntityAbstract[]
     */
    public function getTasks(int $limit = 100, int $shardMax = 1, int $shardNum = 1)
    {
        return $this->tasks;
    }

    /**
     * @return void
     */
    public function clearMemory()
    {
        // do nothing
    }


    /**
     * @param TaskEntityAbstract $task
     * @param string             $workerId
     *
     * @return int
     */
    public function lock(TaskEntityAbstract $task, $workerId)
    {
        if (empty($task->getWorkerId())) {
            $task->setWorkerId($workerId);

            return 1;
        }

        return 0;
    }


    /**
     * @param TaskEntityAbstract $task
     *
     * @return int
     */
    public function save(TaskEntityAbstract $task)
    {
        return 1;
    }
}