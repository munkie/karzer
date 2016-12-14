<?php

namespace Karzer\PHPUnit\Util;

use Karzer\Job\Job;

class ResultProcessor
{
    /**
     * @var \ReflectionMethod
     */
    private $method;

    /**
     * @var \PHPUnit_Util_PHP_Default
     */
    private $php;

    public function __construct()
    {
        $this->php = new \PHPUnit_Util_PHP_Default();

        $reflectionObject = new \ReflectionObject($this->php);
        $method = $reflectionObject->getMethod('processChildResult');
        $method->setAccessible(true);

        $this->method = $method;
    }

    /**
     * Populates Job test result with data from child process stderr and stdout
     *
     * @see \PHPUnit_Util_PHP_Default::processChildResult()
     *
     * @param Job $job
     */
    public function processJobResult(Job $job)
    {
        try {
            $this->method->invoke(
                $this->php,
                $job->getTest(),
                $job->getResult(),
                $job->getStdout()->getBuffer(),
                $job->getStderr()->getBuffer()
            );
        } catch (\ErrorException $e) {
            $job->addError(
                new \PHPUnit_Framework_Exception(
                    $job->getStdout()->getBuffer(true),
                    0,
                    $e
                )
            );
        }
    }
}
