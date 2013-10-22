<?php

namespace Karzer\TextUI;

use Karzer\Framework\TestSuite;
use PHPUnit_TextUI_Command;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestSuite;

class Command extends PHPUnit_TextUI_Command
{
    /**
     * @param bool $exit
     * @return int
     */
    public static function main($exit = true)
    {
        $command = new static;
        return $command->run($_SERVER['argv'], $exit);
    }

    protected function handleArguments(array $argv)
    {
        parent::handleArguments($argv);
        if (isset($this->arguments['test']) && $this->arguments['test'] instanceof PHPUnit_Framework_TestSuite) {
            $this->arguments['test'] = new TestSuite($this->arguments['test']);
        }
    }
}
