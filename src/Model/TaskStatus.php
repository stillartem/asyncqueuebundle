<?php

namespace Drivenow\AsyncWorkersBundle\Model;


class TaskStatus
{
    /* @var string * */
    const FREE = 'Free';

    /* @var string * */
    const INPROGRESS = 'In Progress';

    /* @var string * */
    const DONE = 'Done';

    /* @var string * */
    const ERROR = 'Error';

    /* @var string * */
    const POSTPONED = 'Postponed';

    /* @var string * */
    private $status;


    /**
     * @param string $status
     *
     * @throws \Exception
     */
    public function __construct($status)
    {
        if (!in_array($status, [self::FREE, self::DONE, self::ERROR, self::INPROGRESS])) {
            throw new \Exception('Invalid task status: ' . $status);
        }
        $this->status = $status;
    }


    /**
     * @return string
     */
    public function getValue()
    {
        return $this->status;
    }


    /**
     * @return string
     */
    public function __toString()
    {
        return $this->status;
    }
}