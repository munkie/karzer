<?php

namespace Karzer\Job;

use Karzer\Exception\ForkException;
use Karzer\Exception\RuntimeException;

class JobPool implements \IteratorAggregate, \Countable
{
    /**
     * Pending jobs queue
     *
     * @var \SplQueue|Job[]
     */
    private $queue;

    /**
     * Active job threads pool
     *
     * @var Pool|Job[]
     */
    private $threads;

    /**
     * @param int $size
     */
    public function __construct($size)
    {
        $this->threads = new Pool($size);
        $this->queue = new \SplQueue();
    }

    /**
     * Add job to pool queue
     *
     * @param Job $job
     */
    public function enqueue(Job $job)
    {
        $this->queue->enqueue($job);
    }

    /**
     * Remove job from pool
     *
     * @param Job $job
     * @return bool If tests execution should be stopped
     */
    public function stop(Job $job)
    {
        $shouldStop = $this->finishJob($job);
        $this->removeJob($job);

        return $shouldStop;
    }

    /**
     * Fill pool with pending jobs
     */
    public function fillPool()
    {
        while (!$this->threads->isFull() && !$this->queue->isEmpty()) {
            $job = $this->queue->dequeue();
            $this->addJob($job);
        }
    }
    /**
     * Add job to pool
     *
     * @param Job $job
     * @throws \Karzer\Exception\RuntimeException
     */
    private function addJob(Job $job)
    {
        $threadId = $this->threads->add($job);
        $job->setThreadId($threadId);
        $this->startJob($job);
    }

    private function removeJob(Job $job)
    {
        $this->threads->remove($job);
        $this->fillPool();
    }

    /**
     * Start job execution
     *
     * @param Job $job
     */
    private function startJob(Job $job)
    {
        try {
            $job->startTest();
        } catch (ForkException $exception) {
            $job->addError($exception);
            $this->removeJob($job);
        }
    }

    /**
     * Finish job execution
     *
     * @param Job $job
     * @return bool If tests execution should be stopped
     */
    private function finishJob(Job $job)
    {
        $job->endTest();
        return $job->getResult()->shouldStop();
    }

    /**
     * Get all open stdin and stderr streams of pool jobs
     *
     * @return resource[]
     */
    public function getStreams()
    {
        $streams = [];
        foreach ($this as $job) {
            $streams[] = $job->getOpenStreams();
        }

        return count($streams) > 0 ? array_merge(...$streams) : [];
    }

    /**
     * Get pool job by stream resource
     *
     * @param resource $resource
     * @return Job Job associated with stream resource
     *
     * @throws \Karzer\Exception\RuntimeException
     */
    public function getJobByStream($resource)
    {
        foreach ($this as $job) {
            if ($job->hasStream($resource)) {
                return $job;
            }
        }

        throw new RuntimeException('No job is associated with stream');
    }

    /**
     * Get jobs from pool slots
     *
     * @return Job[]|\Traversable
     */
    public function getIterator()
    {
        return $this->threads;
    }

    /**
     * Number of pool slots allocated with jobs
     *
     * @return int
     */
    public function count()
    {
        return $this->threads->count();
    }

    /**
     * All pool slots are allocated with jobs
     *
     * @return bool
     */
    public function isFull()
    {
        return $this->threads->isFull();
    }

    /**
     * Pool has no jobs
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->threads->isEmpty();
    }
}
