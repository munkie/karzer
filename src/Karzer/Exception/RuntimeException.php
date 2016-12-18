<?php

namespace Karzer\Exception;

class RuntimeException extends \RuntimeException implements KarzerException
{

    /**
     * Process open failed
     *
     * @param \Exception $exception Exception thrown
     * @param string $command Process exec command
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
