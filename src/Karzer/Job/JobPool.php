<?php

namespace Karzer\Job;

use Karzer\Exception\RuntimeException;

class JobPool implements \IteratorAggregate, \Countable
{
    /**
     * @var int max number of pools
     */
    protected $max;

    /**
     * @var \SplQueue|Job[]
     */
    protected $queue;

    /**
     * @var \SplObjectStorage|Job[]
     */
    protected $jobs;

    /**
     * @var \SplFixedArray|Job[]
     */
    protected $positions;

    /**
     * @param int $max
     */
    public function __construct($max)
    {
        $this->max = (int) $max;
        $this->jobs = new \SplObjectStorage();
        $this->queue = new \SplQueue();
        $this->positions = new \SplFixedArray($this->max);
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
     * Dequeue pending job from queue
     *
     * @return Job
     */
    public function dequeue()
    {
        return $this->queue->dequeue();
    }

    /**
     * If job queue is empty
     *
     * @return bool
     */
    public function isQueueEmpty()
    {
        return $this->queue->isEmpty();
    }

    /**
     * Add job to pool
     *
     * @param Job $job
     * @throws \Karzer\Exception\RuntimeException
     */
    public function add(Job $job)
    {
        $this->setJobPosition($job);
        $this->jobs->attach($job);
    }

    /**
     * @param Job $job
     */
    public function remove(Job $job)
    {
        $this->freeJobPosition($job);
        $this->jobs->detach($job);
    }

    /**
     * @param Job $job
     * @throws \Karzer\Exception\RuntimeException
     */
    protected function setJobPosition(Job $job)
    {
        $position = 0;
        do {
            if (!isset($this->positions[$position])) {
                $this->positions[$position] = $job;
                $job->setThreadId($position);
                return;
            }
        } while (++$position < $this->max);

        throw new RuntimeException('No free pool positions available');
    }

    /**
     * Remove job from pool slot
     *
     * @param Job $job
     * @throws \Karzer\Exception\RuntimeException
     */
    protected function freeJobPosition(Job $job)
    {
        foreach ($this->positions as $position => $positionJob) {
            if ($job === $positionJob) {
                unset($this->positions[$position]);
                return;
            }
        }
        throw new RuntimeException('Job has no pool position');
    }

    /**
     * Get all stdin and stderr streams of pool jobs
     *
     * @return resource[]
     */
    public function getStreams()
    {
        $streams = [];
        foreach ($this as $job) {
            $streams[] = $job->getOpenStreams();
        }

        return array_merge(...$streams);
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
        return $this->jobs;
    }

    /**
     * Number of pool slots allocated with jobs
     *
     * @return int
     */
    public function count()
    {
        return $this->jobs->count();
    }

    /**
     * All pool slots are allocated with jobs
     *
     * @return bool
     */
    public function isFull()
    {
        return $this->max === $this->count();
    }

    /**
     * Pool has no jobs
     *
     * @return bool
     */
    public function isEmpty()
    {
        return 0 === $this->count();
    }
}
