<?php

namespace Karzer\PHPUnit\Framework;

use Karzer\Job\JobRunner;

class TestSuite extends \PHPUnit_Framework_TestSuite
{
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

        $this->runner = $runner;

        $this->addTests($test);
    }

    /**
     * Get all tests from test or test suite
     *
     * @param \PHPUnit_Framework_Test $test
     */
    private function addTests(\PHPUnit_Framework_Test $test)
    {
        foreach ($this->getSuiteTests($test) as $suiteTest) {
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

        return array_merge(...$tests);
    }

    /**
     * Run all tests
     */
    protected function tearDown()
    {
        $this->runner->run();
    }

}
