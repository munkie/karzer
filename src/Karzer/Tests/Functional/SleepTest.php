<?php

namespace Karzer\Tests\Functional;

use Karzer\Framework\TestCase\TestCase;

class SleepTest extends TestCase
{
    /**
     * @dataProvider sleepProvider
     * @param int $sec
     */
    public function testSleep($sec, $i)
    {
        $this->assertEquals(0, sleep($sec));
        echo "[$i:$sec]";
    }

    /**
     * @return array
     */
    public function sleepProvider()
    {
        return array(
            array(8, 0),
            array(4, 1),
            array(2, 2),
            array(1, 3),
            array(8, 4),
            array(4, 5),
            array(2, 6),
            array(1, 7),
            array(8, 8),
            array(4, 9),
            array(2, 10),
            array(1, 11),
            array(8, 12),
            array(4, 13),
            array(2, 14),
            array(1, 15),
        );
    }
}
