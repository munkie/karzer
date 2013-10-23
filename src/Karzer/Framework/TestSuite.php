<?php

namespace Karzer\Framework;

use Karzer\Util\Job\JobRunner;
use Karzer\Framework\TestCase;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Framework_TestResult;

class TestSuite extends PHPUnit_Framework_TestSuite
{
    /**
     * @var JobRunner
     */
    protected $runner;

    /**
     * @param PHPUnit_Framework_TestSuite $testSuite
     */
    public function __construct(PHPUnit_Framework_TestSuite $testSuite)
    {
        foreach ($this->getSuiteTests($testSuite) as $test) {
            $this->addTest($test);
        }

        $this->runner = new JobRunner();
    }

    /**
     * @param PHPUnit_Framework_TestSuite $testSuite
     * @return PHPUnit_Framework_Test[]
     */
    protected function getSuiteTests(PHPUnit_Framework_TestSuite $testSuite)
    {
        $tests = array();
        foreach ($testSuite->tests() as $test) {
            if ($test instanceof PHPUnit_Framework_TestSuite) {
                $suiteTests = $this->getSuiteTests($test);
                $tests = array_merge($tests, $suiteTests);
            } else {
                $tests[] = $test;
            }
        }
        return $tests;
    }

    /**
     * @param PHPUnit_Framework_Test $test
     * @param PHPUnit_Framework_TestResult $result
     */
    public function runTest(PHPUnit_Framework_Test $test, PHPUnit_Framework_TestResult $result)
    {
        if ($test instanceof TestCase) {
            $job = $test->createJob($result);
            $this->runner->getNext($job);
        }

    }
}