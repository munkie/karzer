<?php

namespace Karzer\TextUI;

use Karzer\Framework\TestSuite;
use PHPUnit_TextUI_Command;
use PHPUnit_Framework_TestSuite;
use PHPUnit_Util_InvalidArgumentHelper;

class Command extends PHPUnit_TextUI_Command
{
    public function __construct()
    {
        $this->longOptions['threads='] = 'handleThreads';
        $this->arguments['threads'] = 2;
        $this->arguments['processIsolation'] = true;
    }

    /**
     * @param bool $exit
     * @return int
     */
    public static function main($exit = true)
    {
        $command = new self;
        return $command->run($_SERVER['argv'], $exit);
    }

    /**
     * @param string $value
     * @throws \PHPUnit_Framework_Exception
     */
    protected function handleThreads($value)
    {
        if ((string) (int) $value !== (string) $value) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(1, 'integer');
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
        if (isset($this->arguments['test']) && $this->arguments['test'] instanceof PHPUnit_Framework_TestSuite) {
            $suite = $this->arguments['test'];
        } else {
            $suite = $this->createRunner()->getTest(
                $this->arguments['test'],
                $this->arguments['testFile'],
                $this->arguments['testSuffixes']
            );
        }
        return new TestSuite($suite, $this->arguments['threads']);
    }
}
