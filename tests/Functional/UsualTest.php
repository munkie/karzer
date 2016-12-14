<?php

namespace Karzer\Tests\Functional;

use PHPUnit\Framework\TestCase;

class UsualTest extends TestCase
{

    public function testTrue()
    {
        static::assertTrue(true);
    }

    public function testFalse()
    {
        static::assertTrue(false);
    }
}
