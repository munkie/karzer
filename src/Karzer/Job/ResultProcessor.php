<?php

namespace Karzer\Job;

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
     * @param \PHPUnit_Framework_Test $test
     * @param \PHPUnit_Framework_TestResult $result
     * @param string $stdout
     * @param string $stderr
     *
     * @throws \PHPUnit_Framework_Exception
     */
    public function processChildResult(
        \PHPUnit_Framework_Test $test,
        \PHPUnit_Framework_TestResult $result,
        $stdout,
        $stderr
    ) {
        try {
            $this->method->invoke(
                $this->php,
                $test,
                $result,
                $stdout,
                $stderr
            );
        } catch (\ErrorException $errorException) {
            throw new \PHPUnit_Framework_Exception(
                trim($stdout),
                0,
                $errorException
            );
        }
    }
}
