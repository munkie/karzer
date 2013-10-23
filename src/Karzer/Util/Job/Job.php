<?php

namespace Karzer\Util\Job;

use Karzer\Framework\TestCase;
use Text_Template;
use PHPUnit_Framework_Test;
use PHPUnit_Framework_TestResult;

class Job
{
    /**
     * @var \Text_Template
     */
    protected $template;

    /**
     * @var TestCase
     */
    protected $test;

    /**
     * @var \PHPUnit_Framework_TestResult
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
     * @var resource
     */
    protected $process;

    /**
     * @var Stream
     */
    protected $stderr;

    /**
     * @var Stream
     */
    protected $stdout;

    /**
     * @param Text_Template $template
     * @param TestCase $test
     * @param PHPUnit_Framework_TestResult $result
     */
    public function __construct(
        Text_Template $template,
        TestCase $test,
        PHPUnit_Framework_TestResult $result
    ) {
        $this->template = $template;
        $this->test = $test;
        $this->result = $result;
    }

    /**
     * @return \PHPUnit_Framework_TestResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \Text_Template
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return \PHPUnit_Framework_Test
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
        $this->modifyTemplate();
        return $this->template->render();
    }

    /**
     * XXX dirty hack of template
     */
    protected function modifyTemplate()
    {
        $property = new \ReflectionProperty($this->template, 'template');
        $property->setAccessible(true);
        $template = $property->getValue($this->template);

        $modifiedTemplate = str_replace(
            "\$test->setInIsolation(TRUE);\n",
            "\$test->setInIsolation(TRUE);\n    \$test->setPoolPosition({poolPosition});\n",
            $template
        );

        $property->setValue($this->template, $modifiedTemplate);

        $this->template->setVar(array('poolPosition' => $this->getPoolPosition()));
    }

    public function startTest()
    {
        $this->result->startTest($this->test);
        $this->backupErrorHandlerSettings();
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
     * @param resource $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }

    /**
     * @return resource
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param Stream $stderr
     */
    public function setStderr(Stream $stderr)
    {
        $this->stderr = $stderr;
    }

    /**
     * @return Stream
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * @param Stream $stdout
     */
    public function setStdout(Stream $stdout)
    {
        $this->stdout = $stdout;
    }

    /**
     * @return Stream
     */
    public function getStdout()
    {
        return $this->stdout;
    }

    /**
     * @param resource $stream
     * @return bool
     */
    public function hasStream($stream)
    {
        return $this->stdout->isEqualTo($stream) || $this->stderr->isEqualTo($stream);
    }

    /**
     * @param resource $stream
     * @return Stream|null
     */
    public function getStream($stream)
    {
        if ($this->stdout->isEqualTo($stream)) {
            return $this->stdout;
        } elseif ($this->stderr->isEqualTo($stream)) {
            return $this->stderr;
        } else {
            return null;
        }
    }

    /**
     * @return bool
     */
    public function isClosed()
    {
        return !$this->stderr->isOpen() && !$this->stdout->isOpen();
    }

    /**
     * @param int $poolNumber
     */
    public function setPoolPosition($poolNumber)
    {
        $this->poolPosition = $poolNumber;
    }

    /**
     * @return int
     */
    public function getPoolPosition()
    {
        return $this->poolPosition;
    }
}
