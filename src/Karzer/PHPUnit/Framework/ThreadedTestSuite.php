<?php

namespace Karzer\PHPUnit\Framework;

use Karzer\Job\JobRunner;
use PHPUnit_Framework_TestResult;

class ThreadedTestSuite extends \PHPUnit_Framework_TestSuite
{

    /**
     * @var \PHPUnit_Framework_Test
     */
    private $baseTestSuite;

    /**
     * @var JobRunner
     */
    private $runner;

    /**
     * @param \PHPUnit_Framework_Test $test
     * @param JobRunner $runner
     */
    public function __construct(\PHPUnit_Framework_Test $test, JobRunner $runner)
    {
        parent::__construct();

        $this->baseTestSuite = $test;
        $this->runner = $runner;
    }

    /**
     * {@inheritdoc}
     */
    public function run(PHPUnit_Framework_TestResult $result = null)
    {
        $this->initTests();

        return parent::run($result);
    }

    /**
     * Add all tests from base test suite
     */
    private function initTests()
    {
        foreach ($this->getSuiteTests($this->baseTestSuite) as $suiteTest) {
            $groups = ($suiteTest instanceof \PHPUnit_Framework_TestCase) ? $suiteTest->getGroups() : [];
            $this->addTest($suiteTest, $groups);
        }
    }

    /**
     * @param \PHPUnit_Framework_TestSuite|\PHPUnit_Framework_Test $testSuite
     * @return \PHPUnit_Framework_Test[]|\PHPUnit_Framework_TestCase[]
     */
    private function getSuiteTests(\PHPUnit_Framework_Test $testSuite)
    {
        if (!$testSuite instanceof \Traversable) {
            return [$testSuite];
        }

        $tests = [];

        foreach ($testSuite as $test) {
            $tests[] = $this->getSuiteTests($test);
        }

        return count($tests) > 0 ? array_merge(...$tests) : [];
    }

    /**
     * Run all tests
     */
    protected function tearDown()
    {
        $this->runner->run();
    }

}
