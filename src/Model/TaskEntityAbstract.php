<?php

namespace Drivenow\AsyncWorkersBundle\Model;


abstract class TaskEntityAbstract
{
    /** @var int */
    protected $id;

    /** @var int */
    protected $workerId;

    /** @var TaskStatus */
    protected $status;

    /** @var \DateTime */
    protected $nextExecTime;

    /** @var string */
    protected $lastError;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param int $workerId
     *
     * @return TaskEntityAbstract
     */
    public function setWorkerId($workerId)
    {
        $this->workerId = $workerId;

        return $this;
    }


    /**
     * @return int
     */
    public function getWorkerId()
    {
        return $this->workerId;
    }


    /**
     * @param string $status
     *
     * @return TaskEntityAbstract
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }


    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * @param string $lastError
     *
     * @return TaskEntityAbstract
     */
    public function setLastError($lastError)
    {
        $this->lastError = $lastError;

        return $this;
    }


    /**
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }


    /**
     * @param \DateTime $nextExecTime
     *
     * @return TaskEntityAbstract
     */
    public function setNextExecTime($nextExecTime)
    {
        $this->nextExecTime = $nextExecTime;

        return $this;
    }


    /**
     * @return \DateTime
     */
    public function getNextExecTime()
    {
        return $this->nextExecTime;
    }


    /**
     * @return string
     */
    public function getClass()
    {
        return static::class;
    }
}