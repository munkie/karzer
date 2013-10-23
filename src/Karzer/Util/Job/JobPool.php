<?php

namespace Karzer\Util\Job;

use Karzer\Framework\Exception;
use SplObjectStorage;
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
     * @var SplObjectStorage|Job[]
     */
    protected $jobs;

    /**
     * @var int
     */
    protected $positions = array();

    /**
     * @param int $max
     */
    public function __construct($max)
    {
        $this->max = $max;
        $this->jobs = new SplObjectStorage();
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
     * @throws \Karzer\Framework\Exception
     */
    protected function setPoolNumber(Job $job)
    {
        $position = 0;
        do {
            if (!isset($this->positions[$position])) {
                $this->positions[$position] = $job;
                $job->setPoolNumber($position);
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
     * @param Job $job
     */
    public function remove(Job $job)
    {
        $this->removePoolNumber($job);
        $this->jobs->detach($job);
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @return bool
     */
    public function isFull()
    {
        return $this->count() == $this->max;
    }

    /**
     * @return resource[]
     */
    public function getStreams()
    {
        $streams = array();
        foreach ($this->jobs as $job) {
            $streams[] = $job->getStdout()->getStream();
            $streams[] = $job->getStderr()->getStream();
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
    public function isEmpty()
    {
        return 0 == $this->count();
    }
}