<?php

$filename = dirname(PHPUNIT_COMPOSER_INSTALL) . '/phpunit/phpunit/src/Util/PHP.php';
$code = file_get_contents($filename);
$code = str_replace(
    'abstract class PHPUnit_Util_PHP',
    'abstract class PHPUnit_Util_PHP_Abstract',
    $code
);
$code = '?>'.$code;

eval($code);

use Karzer\Job\JobFactory;

abstract class PHPUnit_Util_PHP extends PHPUnit_Util_PHP_Abstract
{
    /**
     * @var JobFactory
     */
    private static $jobFactory;

    /**
     * Register JobFactory
     *
     * @param JobFactory $factory
     */
    public static function setFactory(JobFactory $factory)
    {
        static::$jobFactory = $factory;
    }

    /**
     * @return JobFactory
     */
    public static function factory()
    {
        return static::$jobFactory;
    }
}
