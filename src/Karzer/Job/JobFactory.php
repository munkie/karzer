<?php

namespace Karzer\Job;

use Karzer\PHPUnit\Util\ResultProcessor;

/**
 * Dirty hack. JobFactory is returned by monkey-patched \PHPUnit_Util_PHP::factory() method
 * And instead of running test in isolated process
 * It creates job for test and adds test to job pool of runner
 */
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
     * @param ResultProcessor $resultProcessor
     */
    public function __construct(JobRunner $runner, ResultProcessor $resultProcessor)
    {
        $this->runner = $runner;
        $this->resultProcessor = $resultProcessor;
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
