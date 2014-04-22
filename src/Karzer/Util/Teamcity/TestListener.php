<?php

namespace Karzer\Util\Teamcity;

use Karzer\Framework\TestCase\JobTestInterface;
use Karzer\Karzer;
use PHPUnit\TeamCity\TestListener as TeamcityTestListener;
use PHPUnit_Framework_Test;

class TestListener extends TeamcityTestListener
{
    /**
     * @var string
     */
    protected $captureStandardOutput = 'false';

    /**
     * @param PHPUnit_Framework_Test $test
     * @return int|void
     */
    protected function getFlowId(PHPUnit_Framework_Test $test)
    {
        if ($test instanceof JobTestInterface) {
            return 'karzer-' . $test->getPoolPosition();
        } else {
            return 'phpunit-' . parent::getFlowId($test);
        }
    }
}
