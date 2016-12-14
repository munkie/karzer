<?php

namespace Karzer\Job;

use Karzer\Exception\ForkException;
use Karzer\PHPUnit\Util\ResultProcessor;
use Karzer\Util\Process;
use Karzer\Util\Stream;

class Job
{

    const THREAD_ENV_TOKEN = 'TEST_TOKEN';

    /**
     * @var \PHPUnit_Framework_Test
     */
    private $test;

    /**
     * @var \PHPUnit_Framework_TestResult
     */
    private $result;

    /**
     * @var int
     */
    private $threadId;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var ResultProcessor
     */
    private $resultProcessor;

    /**
     * @param string $script PHP Script to execute
     * @param \PHPUnit_Framework_Test $test Test case
     * @param \PHPUnit_Framework_TestResult $result Test result
     * @param ResultProcessor $resultProcessor Child process result processor
     */
    public function __construct(
        $script,
        \PHPUnit_Framework_Test $test,
        \PHPUnit_Framework_TestResult $result,
        ResultProcessor $resultProcessor
    ) {
        $this->test = $test;
        $this->result = $result;
        $this->resultProcessor = $resultProcessor;
        $this->process = Process::createPhpProcess($script);
    }

    /**
     * @param int $threadId
     */
    public function setThreadId($threadId)
    {
        $this->threadId = $threadId;
    }

    /**
     * @return \PHPUnit_Framework_TestResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return \PHPUnit_Framework_Test
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @throws ForkException
     */
    public function startTest()
    {
        $this->result->startTest($this->test);
        $this->process->start([self::THREAD_ENV_TOKEN => $this->threadId]);
    }

    public function endTest()
    {
        $this->process->close();
        $this->resultProcessor->processJobResult($this);
    }

    /**
     * @param \PHPUnit_Framework_Exception|\Exception|string $error
     * @param int $time
     */
    public function addError($error, $time = 0)
    {
        if ($error instanceof \PHPUnit_Framework_Exception) {
            $exception = $error;
        } elseif ($error instanceof \Exception) {
            $exception = new \PHPUnit_Framework_Exception($error->getMessage(), 0, $error);
        } else {
            $exception = new \PHPUnit_Framework_Exception($error);
        }
        $this->result->addError(
            $this->test,
            $exception,
            $time
        );
    }

    /**
     * @return Stream
     */
    public function getStderr()
    {
        return $this->process->getStderr();
    }

    /**
     * @return Stream
     */
    public function getStdout()
    {
        return $this->process->getStdout();
    }

    /**
     * @return resource[]
     */
    public function getOpenStreams()
    {
        $streams = [];
        if ($this->getStdout()->isOpen()) {
            $streams[] = $this->getStdout()->getResource();
        }
        if ($this->getStderr()->isOpen()) {
            $streams[] = $this->getStderr()->getResource();
        }
        return $streams;
    }

    /**
     * @param resource $resource
     * @return bool
     */
    public function hasStream($resource)
    {
        return $this->process->getStdout()->isSameResource($resource)
            || $this->process->getStderr()->isSameResource($resource);
    }

    /**
     * @param resource $stream
     *
     * @return bool If stream was closed
     */
    public function readStream($stream)
    {
        $this->getStream($stream)->read();
        return $this->isStreamClosed();
    }

    /**
     * @param resource $stream
     * @return Stream|null
     */
    private function getStream($stream)
    {
        if ($this->getStdout()->isSameResource($stream)) {
            return $this->getStdout();
        }
        if ($this->getStderr()->isSameResource($stream)) {
            return $this->getStderr();
        }

        return null;
    }

    /**
     * @return bool
     */
    private function isStreamClosed()
    {
        return !$this->getStderr()->isOpen() && !$this->getStdout()->isOpen();
    }

}
