<?php

namespace Karzer\Util\Teamcity;

use Karzer\Karzer;
use PHPUnit\TeamCity\TestListener as TeamcityTestListener;
use PHPUnit_Framework_Test;

class TestListener extends TeamcityTestListener
{
    /**
     * @param PHPUnit_Framework_Test $test
     * @return int|void
     */
    protected function getFlowId(PHPUnit_Framework_Test $test)
    {
        return Karzer::getThreadNumber();
    }
}
