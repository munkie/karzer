<?php

namespace Karzer\Framework;

class ErrorException extends \ErrorException
{
    public static function setHandler()
    {
        set_error_handler(function($severity, $message, $filename, $lineno) {
            throw new ErrorException($message, $severity, $severity, $filename, $lineno);
        });
    }

    public static function restoreHandler()
    {
        restore_error_handler();
    }
}
