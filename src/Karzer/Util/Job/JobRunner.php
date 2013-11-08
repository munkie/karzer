<?php

namespace Karzer\Util\Job;

use Karzer\Framework\Exception;
use Karzer\Util\Process;
use Karzer\Util\Stream;
use PHPUnit_Util_PHP_Default;
use PHPUnit_Framework_Exception;
use ErrorException;
use RuntimeException;

class JobRunner extends PHPUnit_Util_PHP_Default
{
    /**
     * @var JobPool|Job[]
     */
    protected $pool;

    /**
     * @var int stream_select timeout
     */
    protected $timeout = null;

    /**
     * @param JobPool $pool
     * @param null $timeout
     */
    public function __construct(JobPool $pool, $timeout = null)
    {
        $this->pool = $pool;
        $this->timeout = $timeout;
    }

    /**
     * @param Job $job
     */
    public function enqueueJob(Job $job)
    {
        $this->pool->enqueue($job);
    }

    public function dequeueJob()
    {
        return $this->pool->dequeue();
    }

    /**
     * @param Job $job
     * @throws \PHPUnit_Framework_Exception
     */
    public function startJob(Job $job)
    {
        $this->pool->add($job);

        $job->start($this->getPhpBinary());
        $job->startTest();
    }

    /**
     * @param Job $job
     */
    public function stopJob(Job $job)
    {
        $this->pool->remove($job);

        $job->stop();

        try {
            $this->processChildResult(
                $job->getTest(),
                $job->getResult(),
                $job->getStdout()->getBuffer(),
                $job->getStderr()->getBuffer()
            );
        } catch (ErrorException $e) {
            $job->getResult()->addError(
                $job->getTest(),
                new PHPUnit_Framework_Exception($job->getStdout()->getBuffer(true), 0, $e),
                0
            );
        }

        $job->endTest();
    }

    /**
     * Fill pool with jobs from queue
     * @return bool
     */
    protected function fillPool()
    {
        while (!$this->pool->isFull()) {
            try {
                $job = $this->pool->dequeue();
                $this->startJob($job);
            } catch (RuntimeException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return Job[]|bool
     * @throws \Karzer\Framework\Exception
     */
    public function run()
    {
        $this->fillPool();

        if ($this->pool->isEmpty()) {
            return false;
        }

        $processedJobs = array();
        do {
            $r = $this->pool->getStreams();
            $w = array();
            $x = array();

            if (count($r) > 0) {
                $result = stream_select($r, $w, $x, $this->timeout);

                if (false === $result) {
                    throw new Exception('Stream select failed');
                }

                foreach ($r as $stream) {
                    foreach ($this->pool as $job) {
                        if ($job->hasStream($stream)) {
                            $job->getStream($stream)->read();
                            if ($job->isClosed()) {
                                $this->stopJob($job);
                                $processedJobs[] = $job;
                                if ($job->getResult()->shouldStop()) {
                                    break(3);
                                }
                                $this->fillPool();
                            }
                        }
                    }
                }
            }
        } while (!$this->pool->isEmpty());

        return $processedJobs;
    }
}
