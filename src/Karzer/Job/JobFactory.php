<?php

namespace Karzer\Job;

use Karzer\PHPUnit\Util\ResultProcessor;

class JobFactory
{
    /**
     * @var JobRunner
     */
    private $runner;

    /**
     * @var ResultProcessor
     */
    private $resultProcessor;

    /**
     * JobFactory constructor.
     *
     * @param JobRunner $runner
     */
    public function __construct(JobRunner $runner)
    {
        $this->runner = $runner;
        $this->resultProcessor = new ResultProcessor();
    }

    /**
     * Create Job and enqueue in runner
     *
     * @param string $script PHP script to run test in isolation mode
     * @param \PHPUnit_Framework_Test $test
     * @param \PHPUnit_Framework_TestResult $result
     */
    public function runTestJob($script, \PHPUnit_Framework_Test $test, \PHPUnit_Framework_TestResult $result)
    {
        $job = new Job($script, $test, $result, $this->resultProcessor);
        $this->runner->enqueueJob($job);
    }
}
