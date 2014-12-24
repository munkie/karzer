<?php

namespace Karzer\Tests\Functional;

class SleepTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider sleepProvider
     * @param int $usec
     */
    public function testSleep($usec)
    {
        usleep($usec);
        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function sleepProvider()
    {
        return array(
            array(1000000),
            array(2000000),
            array(1500000),
            array(150000),
            array(4000000),
        );
    }
}
