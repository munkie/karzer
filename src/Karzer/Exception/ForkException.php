<?php

namespace Karzer\Exception;

class ForkException extends RuntimeException
{

    /**
     * @param \Exception $exception
     * @param string $command
     * @return static
     */
    public static function forkFailed(\Exception $exception, $command)
    {
        return new static(
            sprintf('Failed to fork process "%s"', $command),
            0,
            $exception
        );
    }
}
