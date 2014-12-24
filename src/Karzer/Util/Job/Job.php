<?php

namespace Karzer\Util\Job;

use Karzer\Framework\JobTestInterface;
use Karzer\Framework\TestDecorator;
use Karzer\Util\Process;
use Karzer\Util\Stream;
use PHPUnit_Framework_TestResult;
use PHPUnit_Framework_Exception;
use Text_Template;
use ReflectionProperty;
use Exception;

class Job
{
    /**
     * @var Text_Template
     */
    protected $template;

    /**
     * @var JobTestInterface|TestDecorator
     */
    protected $test;

    /**
     * @var PHPUnit_Framework_TestResult
     */
    protected $result;

    /**
     * @var boolean
     */
    protected $oldErrorHandlerSetting;

    /**
     * @var int
     */
    protected $poolPosition;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @var string
     */
    protected $render;

    /**
     * @param Text_Template $template
     * @param \Karzer\Framework\JobTestInterface $test
     * @param PHPUnit_Framework_TestResult $result
     */
    public function __construct(
        Text_Template $template,
        JobTestInterface $test,
        PHPUnit_Framework_TestResult $result
    ) {
        $this->template = $template;
        $this->test = $test;
        $this->result = $result;
    }

    /**
     * @return PHPUnit_Framework_TestResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return Text_Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return \Karzer\Framework\JobTestInterface
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @return string
     */
    public function render()
    {
        if (null === $this->render) {
            $this->render = $this->test->createTemplate()->render();
        }
        return $this->render;
    }

    /**
     * @param string $php Php executable
     */
    public function start($php)
    {
        $process = new Process($php);
        $process->open();
        $process->writeScript($this->render());

        $this->setProcess($process);
    }

    public function startTest()
    {
        $this->result->startTest($this->test);
        $this->backupErrorHandlerSettings();
    }

    public function stop()
    {
        $this->getProcess()->close();
    }

    public function endTest()
    {
        $this->restoreErrorHandlerSettings();
        $this->test->unsetTestResultObject();
    }

    protected function backupErrorHandlerSettings()
    {
        if ($this->test->useErrorHandler()) {
            $this->oldErrorHandlerSetting = $this->result->getConvertErrorsToExceptions();
        }
    }

    protected function restoreErrorHandlerSettings()
    {
        if ($this->test->useErrorHandler()) {
            $this->result->convertErrorsToExceptions($this->oldErrorHandlerSetting);
        }
    }

    /**
     * @param Process $process
     */
    public function setProcess(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @return Stream
     */
    public function getStderr()
    {
        return $this->getProcess()->getStderr();
    }

    /**
     * @return Stream
     */
    public function getStdout()
    {
        return $this->getProcess()->getStdout();
    }

    /**
     * @param resource $stream
     * @return bool
     */
    public function hasStream($stream)
    {
        return $this->getStdout()->isEqualTo($stream) || $this->getStderr()->isEqualTo($stream);
    }

    /**
     * @param resource $stream
     * @return Stream|null
     */
    public function getStream($stream)
    {
        if ($this->getStdout()->isEqualTo($stream)) {
            return $this->getStdout();
        } elseif ($this->getStderr()->isEqualTo($stream)) {
            return $this->getStderr();
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return !$this->getStderr()->isOpen() && !$this->getStdout()->isOpen();
    }

    /**
     * @param int $poolPosition
     */
    public function setPoolPosition($poolPosition)
    {
        $this->poolPosition = $poolPosition;
        $this->test->setPoolPosition($poolPosition);
    }

    /**
     * @return int
     */
    public function getPoolPosition()
    {
        return $this->poolPosition;
    }

    /**
     * @param PHPUnit_Framework_Exception|Exception|string $error
     * @param int $time
     */
    public function addError($error, $time = 0)
    {
        if ($error instanceof PHPUnit_Framework_Exception) {
            $exception = $error;
        } elseif ($error instanceof Exception) {
            $exception = new PHPUnit_Framework_Exception($error->getMessage(), 0, $error);
        } else {
            $exception = new PHPUnit_Framework_Exception($error);
        }
        $this->getResult()->addError(
            $this->getTest(),
            $exception,
            $time
        );
    }
}
