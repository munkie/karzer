<?php

namespace Karzer\Tests\Functional;

use Karzer\Framework\TestCase\TestCase;

class SampleTest extends TestCase
{
    public function testEquals()
    {
        $this->assertTrue(true);
    }

    /**
     * @param $data
     * @dataProvider dataProvider
     */
    public function testDataProvider($data)
    {
        $this->assertTrue($data);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return array(
            array(true),
            array(false),
            array(null),
            array(new \stdClass()),
            array(array()),
            array("string"),
            array(1),
            array(8.78),
            array("")
        );
    }

    public function testException()
    {
        throw new \Exception("I'm exception, bitch!");
    }
}
