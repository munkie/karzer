<?php

namespace Karzer\PHPUnit\Framework;

use Karzer\Job\JobFactory;
use Karzer\Job\JobRunner;
use Karzer\Util\Reflection;
use SplObjectStorage;

class TestSuite extends \PHPUnit_Framework_TestSuite
{
    /**
     * @var JobRunner
     */
    protected $runner;

    /**
     * :TODO: use TestSuite as delegate
     *
     * @param \PHPUnit_Framework_Test $testSuite
     * @param JobRunner $runner
     */
    public function __construct(\PHPUnit_Framework_Test $testSuite, JobRunner $runner)
    {
        parent::__construct();

        foreach ($this->getSuiteTests($testSuite) as $test) {
            $this->addTest($test);
        }

        $this->runner = $runner;
    }

    /**
     * @param \PHPUnit_Framework_Test|\PHPUnit_Framework_Test[]|\Traversable $testSuite
     * @return \PHPUnit_Framework_Test[]
     */
    protected function getSuiteTests(\PHPUnit_Framework_Test $testSuite)
    {
        if (!$testSuite instanceof \Traversable) {
            return [$testSuite];
        }

        $tests = [];

        foreach ($testSuite as $test) {
            $suiteTests = $this->getSuiteTests($test);
            $tests = array_merge($tests, $suiteTests);
        }

        return $tests;
    }

    /**
     * @param \PHPUnit_Framework_TestResult $result
     * @param string|bool $filter
     * @param string[] $groups
     * @param string[] $excludeGroups
     *
     * @return \PHPUnit_Framework_TestResult
     * @throws \Karzer\Exception\RuntimeException
     */
    public function run(
        \PHPUnit_Framework_TestResult $result = null,
        $filter = false,
        array $groups = [],
        array $excludeGroups = []
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
                if ($test instanceof \PHPUnit_Framework_TestCase ||
                    $test instanceof self) {
                    $test->setbeStrictAboutChangesToGlobalState(
                        Reflection::getObjectValue(
                            $this,
                            'beStrictAboutChangesToGlobalState',
                            \PHPUnit_Framework_TestSuite::class
                        )
                    );
                    $test->setBackupGlobals($this->backupGlobals);
                    $test->setBackupStaticAttributes($this->backupStaticAttributes);
                    $test->setRunTestInSeparateProcess($this->runTestInSeparateProcess);
                }

                // Actually registers test in job pull
                $test->run($result);
            }
        }

        $this->runner->run();

        $result->endTestSuite($this);

        return $result;
    }

    /**
     * Get tests by groups
     *
     * @param string[] $groups
     * @return array|\PHPUnit_Framework_Test[]
     */
    protected function getTests(array $groups = [])
    {
        if (empty($groups)) {
            return $this->tests;
        }

        $tests = new SplObjectStorage;

        foreach ($groups as $group) {
            if (isset($this->groups[$group])) {
                foreach ($this->groups[$group] as $test) {
                    $tests->attach($test);
                }
            }
        }

        return iterator_to_array($tests);
    }

    /**
     * @param \PHPUnit_Framework_Test $test
     * @param string $filter
     * @return bool
     */
    protected function testMatchesFilter(\PHPUnit_Framework_Test $test, $filter)
    {
        if ($filter !== false) {
            $tmp = \PHPUnit_Util_Test::describe($test, false);

            if ($tmp[0] !== '') {
                $name = implode('::', $tmp);
            } else {
                $name = $tmp[1];
            }

            if (preg_match($filter, $name) === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param \PHPUnit_Framework_Test $test
     * @param array $excludeGroups
     * @return bool
     */
    protected function testIsNotInExcludeGroup(\PHPUnit_Framework_Test $test, array $excludeGroups = [])
    {
        if (!empty($excludeGroups)) {
            foreach ($this->groups as $group => $groupTests) {
                if (in_array($group, $excludeGroups, true)) {
                    foreach ($groupTests as $groupTest) {
                        if ($test === $groupTest) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

}
