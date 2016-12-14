<?php

namespace Karzer\Job;

use Karzer\Exception\ForkException;
use Karzer\Exception\RuntimeException;
use Karzer\PHPUnit\Util\ResultProcessor;
use SebastianBergmann\Environment\Runtime;

class JobRunner
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
            $job->startTest();
            return true;
        } catch (ForkException $exception) {
            $this->pool->remove($job);
            $job->failTest($exception);
            return false;
        }
    }

    /**
     * @param Job $job
     */
    public function stopJob(Job $job)
    {
        $job->endTest();

        $this->pool->remove($job);
    }

    /**
     * Fill pool with jobs from queue
     */
    private function fillPool()
    {
        while (!$this->pool->isFull() && !$this->pool->queueIsEmpty()) {
            $job = $this->pool->dequeue();
            $this->startJob($job);
        }
    }

    /**
     * @throws \Karzer\Exception\RuntimeException
     */
    public function run()
    {
        $this->fillPool();

        $shouldStop = false;
        while (!$shouldStop && !$this->pool->isEmpty()) {
            $shouldStop = !$this->processPoolStream();
        }
    }

    /**
     * Listen to pool streams and process jobs
     *
     * @return bool If processing should be stopped
     */
    private function processPoolStream()
    {
        $read = $this->pool->getStreams();

        if (0 === count($read)) {
            return true;
        }

        $write = $except = [];

        $result = stream_select($read, $write, $except, null, $this->timeout);

        if (false === $result) {
            throw new RuntimeException('Stream select failed');
        }

        // No changes in streams during timeout, will try one more time
        if (0 === $result) {
            return true;
        }

        foreach ($read as $stream) {
            $job = $this->pool->getJobByStream($stream);
            if (!$this->processPoolJob($job, $stream)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Read job stream and handle job close
     *
     * @param Job $job
     * @param resource $stream
     * @return bool false - test execution should be stopped
     */
    private function processPoolJob(Job $job, $stream)
    {
        $job->getStream($stream)->read();

        if ($job->isStreamClosed()) {
            $this->stopJob($job);
            if ($job->getResult()->shouldStop()) {
                return false;
            }
            $this->fillPool();
        }

        return true;
    }

    /**
     * @param int $maxThreads
     * @return static
     */
    public static function fromThreads($maxThreads)
    {
        return new static(
            new JobPool($maxThreads)
        );
    }

}
