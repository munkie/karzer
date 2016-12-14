<?php

namespace Karzer\PHPUnit\TextUI;

use Karzer\Job\JobFactory;
use Karzer\Job\JobRunner;
use Karzer\PHPUnit\Framework\TestSuite;

class Command extends \PHPUnit_TextUI_Command
{
    public function __construct()
    {
        $this->longOptions['threads='] = 'handleThreads';
        $this->arguments['threads'] = 2;
        $this->arguments['processIsolation'] = true;
    }

    /**
     * @param string $value
     * @throws \PHPUnit_Framework_Exception
     */
    protected function handleThreads($value)
    {
        if ((string) (int) $value !== (string) $value) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(1, 'integer');
        }
        $this->arguments['threads'] = (int) $value;
    }

    /**
     * @param array $argv
     */
    protected function handleArguments(array $argv)
    {
        parent::handleArguments($argv);

        $this->arguments['test'] = $this->createTestSuite();
    }

    /**
     * @return TestSuite
     */
    protected function createTestSuite()
    {
        if (isset($this->arguments['test']) && $this->arguments['test'] instanceof \PHPUnit_Framework_TestSuite) {
            $suite = $this->arguments['test'];
        } else {
            $suite = $this->createRunner()->getTest(
                $this->arguments['test'],
                $this->arguments['testFile'],
                $this->arguments['testSuffixes']
            );
        }

        $runner = JobRunner::fromThreads($this->arguments['threads']);
        $jobFactory = new JobFactory($runner);

        \PHPUnit_Util_PHP::setFactory($jobFactory);

        return new TestSuite($suite, $runner);
    }
}
