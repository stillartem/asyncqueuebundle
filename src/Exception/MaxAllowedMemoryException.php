<?php

namespace Drivenow\AsyncWorkersBundle\Exception;


class MaxAllowedMemoryException extends \RuntimeException
    implements WorkerLimitExceptionInterface
{
    /**
     * @param string $memoryLimit
     * @param string $memoryUsed
     *
     * @return MaxAllowedMemoryException
     */
    public static function forLimits($memoryLimit, $memoryUsed) : self
    {
        return new self(
            sprintf(
                "Memory limit was reached. Allowed '%s', Used: '%s'",
                $memoryLimit,
                $memoryUsed
            )
        );
    }
}