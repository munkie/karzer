<?php

namespace Karzer\Tests\Functional;

use PHPUnit\Framework\TestCase;

class UsualTest extends TestCase
{

    public function testTrue()
    {
        static::assertTrue(true);
    }

    /**
     * @group abc
     */
    public function testFalse()
    {
        static::assertTrue(false);
    }

    public function testFail()
    {
        static::fail(sprintf('TEST_TOKEN: %s', $_SERVER['TEST_TOKEN']));
    }

    public function testEnv()
    {
        static::fail(sprintf('SYMFONY_DEBUG: %s', $_SERVER['SYMFONY_DEBUG']));
    }
}
