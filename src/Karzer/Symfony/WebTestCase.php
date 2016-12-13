<?php

namespace Karzer\Symfony;

use Karzer\Job\JobTestInterface;
use Karzer\Job\JobTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseTestCase;

abstract class WebTestCase extends BaseTestCase implements JobTestInterface
{
    use JobTestTrait;

    /**
     * @return string
     */
    protected static function getPhpUnitXmlDir()
    {
        $oldScript = $_SERVER['argv'][0];
        // just to make sure parent check will proceed
        $_SERVER['argv'][0] = '/bin/phpunit';
        $dir = parent::getPhpUnitXmlDir();
        $_SERVER['argv'][0] = $oldScript;
        return $dir;
    }

    protected static function loadKernelClass()
    {
        if (null === static::$class) {
            static::$class = static::getKernelClass();
        }
    }

    /**
     * @param \PHPUnit_Framework_TestResult $result
     * @return \PHPUnit_Framework_TestResult
     */
    public function run(\PHPUnit_Framework_TestResult $result = null)
    {
        // include kernel to be sure that serialized result containing it will be successfully unserialized
        static::loadKernelClass();
        return parent::run($result);
    }
}
