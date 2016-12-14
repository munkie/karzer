<?php

namespace Karzer\Exception;

class SerializableException extends FrameworkException implements \PHPUnit_Framework_SelfDescribing
{
    /**
     * @param \Exception $e
     */
    public function __construct(\Exception $e)
    {
        $message = $this->formatMessage($e);
        parent::__construct($message);
    }

    /**
     * @param \Exception $e
     * @return string
     */
    protected function formatMessage(\Exception $e)
    {
        $message = '';

        while ($e) {

            $message.= $message ? "\nCaused by\n" : '';

            $message.= \PHPUnit_Framework_TestFailure::exceptionToString($e). "\n";
            $message.= \PHPUnit_Util_Filter::getFilteredStacktrace($e);

            $e = $e->getPrevious();
        }

        return $message;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['message'];
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return $this->getMessage();
    }

    /**
     * @param \Exception $e
     * @return \Exception|SerializableException
     */
    public static function factory(\Exception $e)
    {
        try {
            serialize($e);
        } catch (\Exception $serializeException) {
            $e = new static($e);
        }
        return $e;
    }
}
