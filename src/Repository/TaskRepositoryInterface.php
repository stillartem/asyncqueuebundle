<?php

namespace Drivenow\AsyncWorkersBundle\Repository;


use Drivenow\AsyncWorkersBundle\Exception\PostponedException;
use Drivenow\AsyncWorkersBundle\Model\TaskEntityAbstract;

interface TaskRepositoryInterface
{
    /**
     * @param TaskEntityAbstract $task
     *
     * @param       string $workerId
     *
     * @return bool
     */
    public function lock(TaskEntityAbstract $task, $workerId);


    /**
     * @param TaskEntityAbstract $task
     *
     * @return void
     */
    public function markAsDone(TaskEntityAbstract $task);


    /**
     * @param TaskEntityAbstract $task
     *
     * @param \Throwable $exception
     *
     * @return void
     */
    public function markAsError(TaskEntityAbstract $task, \Throwable $exception);


    /**
     * @param TaskEntityAbstract $task
     *
     * @param PostponedException $exception
     *
     * @return void
     */
    public function markAsPostponed(TaskEntityAbstract $task, PostponedException $exception);


    /**
     * @param TaskEntityAbstract $task
     *
     * @return void
     */
    public function save(TaskEntityAbstract $task);


    /**
     * @param int $limit
     * @param int $shardMax
     * @param int $shardNum
     *
     * @return \Drivenow\AsyncWorkersBundle\Model\TaskEntityAbstract[]
     */
    public function getTasks(int $limit = 100, int $shardMax = 1, int $shardNum = 1);


    /**
     * @return void
     */
    public function clearMemory();
}