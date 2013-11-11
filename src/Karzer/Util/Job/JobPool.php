<?php

namespace Karzer\Util\Job;

use Karzer\Framework\Exception;
use SplObjectStorage;
use SplQueue;
use SplFixedArray;
use IteratorAggregate;
use Traversable;
use Countable;

class JobPool implements IteratorAggregate, Countable
{
    /**
     * @var int max number of pools
     */
    protected $max;

    /**
     * @var SplQueue|Job[]
     */
    protected $queue;

    /**
     * @var SplObjectStorage|Job[]
     */
    protected $jobs;

    /**
     * @var SplFixedArray|Job[]
     */
    protected $positions;

    /**
     * @param int $max
     */
    public function __construct($max)
    {
        $this->max = (int) $max;
        $this->jobs = new SplObjectStorage();
        $this->queue = new SplQueue();
        $this->positions = new SplFixedArray($this->max);
    }

    /**
     * @param Job $job
     */
    public function enqueue(Job $job)
    {
        $this->queue->enqueue($job);
    }

    /**
     * @return Job
     */
    public function dequeue()
    {
        return $this->queue->dequeue();
    }

    /**
     * @return bool
     */
    public function queueIsEmpty()
    {
        return $this->queue->isEmpty();
    }

    /**
     * @param Job $job
     * @throws \Karzer\Framework\Exception
     */
    public function add(Job $job)
    {
        $this->setPoolNumber($job);
        $this->jobs->attach($job);
    }

    /**
     * @param Job $job
     */
    public function remove(Job $job)
    {
        $this->removePoolNumber($job);
        $this->jobs->detach($job);
    }

    /**
     * @param Job $job
     * @throws \Karzer\Framework\Exception
     */
    protected function setPoolNumber(Job $job)
    {
        $position = 0;
        do {
            if (!isset($this->positions[$position])) {
                $this->positions[$position] = $job;
                $job->setPoolPosition($position);
                return;
            }
        } while (++$position < $this->max);
        throw new Exception('No free pool positions available');
    }

    /**
     * @param Job $job
     * @throws \Karzer\Framework\Exception
     */
    protected function removePoolNumber(Job $job)
    {
        foreach ($this->positions as $position => $positionJob) {
            if ($job === $positionJob) {
                unset($this->positions[$position]);
                return;
            }
        }
        throw new Exception('Job has no pool position');
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @return resource[]
     */
    public function getStreams()
    {
        $streams = array();
        foreach ($this->jobs as $job) {
            if ($job->getStdout()->isOpen()) {
                $streams[] = $job->getStdout()->getResource();
            }
            if ($job->getStderr()->isOpen()) {
                $streams[] = $job->getStderr()->getResource();
            }
        }
        return $streams;
    }

    /**
     * @return SplObjectStorage|Traversable
     */
    public function getIterator()
    {
        return $this->jobs;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->jobs->count();
    }

    /**
     * @return bool
     */
    public function isFull()
    {
        return $this->count() == $this->max;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return 0 == $this->count();
    }
}
