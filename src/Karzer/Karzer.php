<?php

namespace Karzer;

class Karzer
{
    const THREAD_NUMBER = 'KARZER_THREAD';

    /**
     * @return bool
     */
    public static function hasThreadNumber()
    {
        return null !== static::getThreadNumber();
    }

    /**
     * @return string|null
     */
    public static function getThreadNumber()
    {
        return isset($_SERVER[self::THREAD_NUMBER]) ? $_SERVER[self::THREAD_NUMBER] : null;
    }

    /**
     * @return string
     */
    public static function getThreadName()
    {
        $threadNumber = static::getThreadNumber();
        return null !== $threadNumber ? "_{$threadNumber}" : '';
    }

    /**
     * @param string $number
     */
    public static function setThreadNumber($number)
    {
        $_SERVER[self::THREAD_NUMBER] = $number;
    }
}
