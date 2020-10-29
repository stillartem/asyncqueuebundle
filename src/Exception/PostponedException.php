<?php

namespace Drivenow\AsyncWorkersBundle\Exception;


use Throwable;

class PostponedException extends \Exception
{
    /** @var \DateTime */
    private $nextExecTime;


    /**
     * PostponedException constructor.
     *
     * @param    \DateTime   $nextTime
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(\DateTime $nextTime, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->nextExecTime = $nextTime;
    }


    /**
     * @return \DateTime
     */
    public function getNextExecTime()
    {
        return $this->nextExecTime;
    }


    /**
     * @param  \DateTime $nextTime
     *
     * @param \Exception $exception
     *
     * @return PostponedException
     */
    public static function create(\DateTime $nextTime, \Exception $exception)
    {
        return new self($nextTime, $exception->getMessage());
    }
}