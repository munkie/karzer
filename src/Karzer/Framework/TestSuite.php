<?php

namespace Karzer\Framework;

use Karzer\Exception\RuntimeException;
use Karzer\Framework\TestCase\JobTestInterface;
use Karzer\Util\Job\JobPool;
use Karzer\Util\Job\JobRunner;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Framework_TestResult;
use PHPUnit_Framework_TestCase;
use PHPUnit_Util_Test;
use SplObjectStorage;

class TestSuite extends PHPUnit_Framework_TestSuite
{
    /**
     * @var JobRunner
     */
    protected $runner;

    /**
     * @param PHPUnit_Framework_Test $testSuite
     * @param int $threads
     */
    public function __construct(PHPUnit_Framework_Test $testSuite, $threads)
    {
        foreach ($this->getSuiteTests($testSuite) as $test) {
            $this->addTest($test);
        }

        $this->runner = new JobRunner(new JobPool($threads));
    }

    /**
     * @param PHPUnit_Framework_Test $testSuite
     * @return PHPUnit_Framework_Test[]
     */
    protected function getSuiteTests(PHPUnit_Framework_Test $testSuite)
    {
        $tests = array();
        if ($testSuite instanceof PHPUnit_Framework_TestSuite) {
            foreach ($testSuite->tests() as $test) {
                if ($test instanceof PHPUnit_Framework_TestSuite) {
                    $suiteTests = $this->getSuiteTests($test);
                    $tests = array_merge($tests, $suiteTests);
                } else {
                    $tests[] = $test;
                }
            }
        } else {
            $tests[] = $testSuite;
        }
        return $tests;
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     * @param string|bool $filter
     * @param array $groups
     * @param array $excludeGroups
     * @return PHPUnit_Framework_TestResult
     * @throws \Karzer\Exception\RuntimeException
     */
    public function run(
        PHPUnit_Framework_TestResult $result = null,
        $filter = false,
        array $groups = array(),
        array $excludeGroups = array()
    ) {
        if ($result === null) {
            $result = $this->createResult();
        }

        $result->startTestSuite($this);

        foreach ($this->getTests($groups) as $test) {
            if ($result->shouldStop()) {
                break;
            }

            $runTest = $this->testMatchesFilter($test, $filter)
                        && $this->testIsNotInExcludeGroup($test, $excludeGroups);

            if ($runTest) {
                if ($test instanceof PHPUnit_Framework_TestCase) {
                    $test->setBackupGlobals($this->backupGlobals);
                    $test->setBackupStaticAttributes($this->backupStaticAttributes);
                    $test->setRunTestInSeparateProcess($this->runTestInSeparateProcess);
                }

                if (!$test instanceof JobTestInterface) {
                    throw new RuntimeException(
                        sprintf(
                            'Test must implement JobTestInterface to be run by karzer - %s',
                            PHPUnit_Util_Test::describe($test)
                        )
                    );
                } elseif (!$test->runTestInSeparateProcess()) {
                    throw new RuntimeException('Tests must by run in process isolation mode');
                } else {
                    $job = $test->createJob($result);
                    $this->runner->enqueueJob($job);
                }
            }
        }

        $this->runner->run();

        $result->endTestSuite($this);

        return $result;
    }

    /**
     * @param array $groups
     * @return array|SplObjectStorage|PHPUnit_Framework_Test[]
     */
    protected function getTests(array $groups = array())
    {
        if (empty($groups)) {
            $tests = $this->tests;
        } else {
            $tests = new SplObjectStorage;

            foreach ($groups as $group) {
                if (isset($this->groups[$group])) {
                    foreach ($this->groups[$group] as $test) {
                        $tests->attach($test);
                    }
                }
            }
        }

        return $tests;
    }

    /**
     * @param PHPUnit_Framework_Test $test
     * @param string $filter
     * @return bool
     */
    protected function testMatchesFilter(PHPUnit_Framework_Test $test, $filter)
    {
        if ($filter !== false) {
            $tmp = PHPUnit_Util_Test::describe($test, false);

            if ($tmp[0] != '') {
                $name = join('::', $tmp);
            } else {
                $name = $tmp[1];
            }

            if (preg_match($filter, $name) == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param PHPUnit_Framework_Test $test
     * @param array $excludeGroups
     * @return bool
     */
    protected function testIsNotInExcludeGroup(PHPUnit_Framework_Test $test, array $excludeGroups = array())
    {
        if (!empty($excludeGroups)) {
            foreach ($this->groups as $_group => $_tests) {
                if (in_array($_group, $excludeGroups)) {
                    foreach ($_tests as $_test) {
                        if ($test === $_test) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }
}
