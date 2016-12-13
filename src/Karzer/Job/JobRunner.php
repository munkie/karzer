<?php

namespace Karzer\Job;

use Karzer\Exception\ForkException;
use Karzer\Exception\RuntimeException;
use SebastianBergmann\Environment\Runtime;

class JobRunner extends \PHPUnit_Util_PHP_Default
{
    /**
     * @var JobPool|Job[]
     */
    protected $pool;

    /**
     * @var int|null stream_select timeout in usec
     */
    protected $timeout = null;

    /**
     * @param JobPool $pool
     */
    public function __construct(JobPool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @param Job $job
     */
    public function enqueueJob(Job $job)
    {
        $this->pool->enqueue($job);
    }

    /**
     * @param Job $job
     * @return bool
     */
    public function startJob(Job $job)
    {
        $this->pool->add($job);

        try {
            $runtime = new Runtime();
            $job->start($runtime->getBinary());
        } catch (ForkException $e) {
            $this->pool->remove($job);
            $job->startTest();
            $job->addError($e);
            $job->endTest();
            return false;
        }

        $job->startTest();
        return true;
    }

    /**
     * @param Job $job
     */
    public function stopJob(Job $job)
    {
        $this->pool->remove($job);

        $job->stop();

        try {
            $reflectionObject = new \ReflectionObject($this);
            $method = $reflectionObject->getMethod('processChildResult');
            $method->setAccessible(true);

            $method->invoke(
                $this,
                $job->getTest(),
                $job->getResult(),
                $job->getStdout()->getBuffer(),
                $job->getStderr()->getBuffer()
            );
        } catch (\ErrorException $e) {
            $job->addError(
                new \PHPUnit_Framework_Exception(
                    $job->getStdout()->getBuffer(true),
                    0,
                    $e
                )
            );
        }

        $job->endTest();
    }

    /**
     * Fill pool with jobs from queue
     */
    protected function fillPool()
    {
        while (!$this->pool->isFull() && !$this->pool->queueIsEmpty()) {
            $job = $this->pool->dequeue();
            $this->startJob($job);
        }
    }

    /**
     * @throws \Karzer\Exception\RuntimeException
     * @return Job[]|bool
     */
    public function run()
    {
        $this->fillPool();

        while (!$this->pool->isEmpty()) {
            $r = $this->pool->getStreams();
            $w = array();
            $x = array();

            if (count($r) > 0) {
                $result = stream_select($r, $w, $x, null, $this->timeout);

                if (false === $result) {
                    throw new RuntimeException('Stream select failed');
                } elseif (0 === $result) {
                    continue;
                }

                foreach ($r as $stream) {
                    foreach ($this->pool as $job) {
                        if ($job->hasStream($stream)) {
                            $job->getStream($stream)->read();
                            if ($job->isClosed()) {
                                $this->stopJob($job);
                                if ($job->getResult()->shouldStop()) {
                                    break(3);
                                }
                                $this->fillPool();
                            }
                        }
                    }
                }
            }
        }
    }
}
