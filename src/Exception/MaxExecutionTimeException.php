<?php

namespace Drivenow\AsyncWorkersBundle\Exception;


class MaxExecutionTimeException extends \RuntimeException
    implements WorkerLimitExceptionInterface
{
    /**
     * @param int $timeout
     *
     * @return self
     */
    public static function forSeconds($timeout) : self
    {
        return new self(
            sprintf(
                "Max execution time '%s sec' was reached",
                $timeout
            )
        );
    }
}