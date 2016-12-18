<?php

namespace Karzer\Job;

use Karzer\Exception\RuntimeException;
use Karzer\Util\Process;
use Karzer\Util\Stream;

/**
 * Job for test that should be run in isolation
 */
class Job
{

    const THREAD_ENV_TOKEN = 'TEST_TOKEN';

    /**
     * Test that will run in isolation
     *
     * @var \PHPUnit_Framework_Test
     */
    private $test;

    /**
     * Test result
     *
     * @var \PHPUnit_Framework_TestResult
     */
    private $result;

    /**
     * Number of thread (starting from 0)
     *
     * @var int
     */
    private $threadId;

    /**
     * Job process
     *
     * @var Process
     */
    private $process;

    /**
     * Result processor to parse results
     *
     * @var ResultProcessor
     */
    private $resultProcessor;

    /**
     * @param \PHPUnit_Framework_Test $test Test case
     * @param \PHPUnit_Framework_TestResult $result Test result
     * @param Process $process PHP process to run job
     * @param ResultProcessor $resultProcessor Child process result processor
     */
    public function __construct(
        \PHPUnit_Framework_Test $test,
        \PHPUnit_Framework_TestResult $result,
        Process $process,
        ResultProcessor $resultProcessor
    ) {
        $this->test = $test;
        $this->result = $result;
        $this->resultProcessor = $resultProcessor;
        $this->process = $process;
    }

    /**
     * Set job pool thread id where job is assigned
     *
     * @param int $threadId
     */
    public function setThreadId($threadId)
    {
        $this->threadId = $threadId;
    }

    /**
     * Get test result
     *
     * @return \PHPUnit_Framework_TestResult
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Get test case
     *
     * @return \PHPUnit_Framework_Test
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * Notify all listeners and start job process
     *
     * @throws RuntimeException When process start failed
     */
    public function startTest()
    {
        $this->result->startTest($this->test);
        $this->process->start([self::THREAD_ENV_TOKEN => $this->threadId]);
    }

    /**
     * Close job process and process job results
     */
    public function endTest()
    {
        $this->process->close();

        try {
            $this->resultProcessor->processChildResult(
                $this->test,
                $this->result,
                $this->getStdout()->getBuffer(),
                $this->getStderr()->getBuffer()
            );
        } catch (\Exception $exception) {
            $this->addError($exception);
        }
    }

    /**
     * Add error to test result
     *
     * @param \PHPUnit_Framework_Exception|\Exception|string $error
     */
    public function addError($error)
    {
        $this->result->addError(
            $this->test,
            $this->convertErrorToFrameworkException($error),
            0
        );
    }

    /**
     * @param \PHPUnit_Framework_Exception|\Exception|string $error
     * @return \PHPUnit_Framework_Exception
     */
    private function convertErrorToFrameworkException($error)
    {
        if ($error instanceof \PHPUnit_Framework_Exception) {
            return $error;
        }
        if ($error instanceof \Exception) {
            return new \PHPUnit_Framework_Exception($error->getMessage(), 0, $error);
        }
        return new \PHPUnit_Framework_Exception($error);
    }

    /**
     * Get open read stream resources of job process, so job runner could listen for changes in them
     *
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
     * Is stream resource is associated with one of job process read streams?
     *
     * @param resource $resource
     * @return bool
     */
    public function hasStream($resource)
    {
        return $this->process->getStdout()->isSameResource($resource)
            || $this->process->getStderr()->isSameResource($resource);
    }

    /**
     * Read new bytes from given job process stream
     *
     * @param resource $stream
     *
     * @return bool If job still has open streams
     */
    public function readStream($stream)
    {
        $this->getStream($stream)->read();
        return $this->hasOpenStreams();
    }

    /**
     * Get job process STDERR
     *
     * @return Stream
     */
    private function getStderr()
    {
        return $this->process->getStderr();
    }

    /**
     * Get job process STDOUT
     *
     * @return Stream
     */
    private function getStdout()
    {
        return $this->process->getStdout();
    }

    /**
     * Get job read strem by resource
     *
     * @param resource $stream
     * @return Stream|null STDOUT ot STDERR stream
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
     * If all stderr and stdout streams are closed
     *
     * @return bool
     */
    private function hasOpenStreams()
    {
        return $this->getStderr()->isOpen() || $this->getStdout()->isOpen();
    }

}
