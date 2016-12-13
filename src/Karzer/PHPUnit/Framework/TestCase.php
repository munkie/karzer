<?php

namespace Karzer\PHPUnit\Framework;

use Karzer\Job\JobTestInterface;
use Karzer\Job\JobTestTrait;

abstract class TestCase extends \PHPUnit_Framework_TestCase implements JobTestInterface
{
    use JobTestTrait;
}
