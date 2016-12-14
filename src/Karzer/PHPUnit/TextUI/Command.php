<?php

namespace Karzer\PHPUnit\TextUI;

use Karzer\Job\JobFactory;
use Karzer\Job\JobPool;
use Karzer\Job\JobRunner;
use Karzer\PHPUnit\Framework\ThreadedTestSuite;
use Karzer\Job\ResultProcessor;

class Command extends \PHPUnit_TextUI_Command
{
    public function __construct()
    {
        $this->longOptions['threads='] = 'handleThreads';
        $this->arguments['threads'] = 2;
        $this->arguments['processIsolation'] = true;
    }

    /**
     * Validate 'threads' value from cli arguments
     *
     * @param string $value
     * @throws \PHPUnit_Framework_Exception If threads is not integer
     */
    protected function handleThreads($value)
    {
        if ((string) (int) $value !== (string) $value) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'integer');
        }
        $this->arguments['threads'] = (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRunner()
    {
        $runner = parent::createRunner();

        $baseTestSuite = $this->getBaseTestSuite($runner);
        $this->arguments['test'] = $this->createTestSuite($baseTestSuite);

        return $runner;
    }

    /**
     * Get base test suite
     *
     * @param \PHPUnit_Runner_BaseTestRunner $runner
     * @return \PHPUnit_Framework_Test
     */
    private function getBaseTestSuite(\PHPUnit_Runner_BaseTestRunner $runner)
    {
        if (array_key_exists('test', $this->arguments)
            && $this->arguments['test'] instanceof \PHPUnit_Framework_Test
        ) {
            return $this->arguments['test'];
        }

        return $runner->getTest(
            $this->arguments['test'],
            $this->arguments['testFile'],
            $this->arguments['testSuffixes']
        );
    }

    /**
     * Create thread test suite
     *
     * @param \PHPUnit_Framework_Test $testSuite
     *
     * @return ThreadedTestSuite
     */
    private function createTestSuite(\PHPUnit_Framework_Test $testSuite)
    {
        $jobPool = new JobPool($this->arguments['threads']);
        $runner = new JobRunner($jobPool);
        $resultProcessor = new ResultProcessor();
        $jobFactory = new JobFactory($runner, $resultProcessor);

        \PHPUnit_Util_PHP::setFactory($jobFactory);

        return new ThreadedTestSuite($testSuite, $runner);
    }

}
