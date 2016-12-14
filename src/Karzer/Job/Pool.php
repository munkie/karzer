<?php

namespace Karzer\Job;

class Pool implements \Countable, \IteratorAggregate
{

    /**
     * Pool storage
     *
     * @var \SplFixedArray
     */
    private $pool;

    /**
     * Current number of jobs in pool
     * @var int
     */
    private $count = 0;

    /**
     * Pool constructor.
     *
     * @param int $size Pool size
     */
    public function __construct($size = 0)
    {
        $this->pool = new \SplFixedArray($size);
    }

    /**
     * Add item
     *
     * @param Job $job
     * @return int Index in pool
     *
     * @throws \RuntimeException If pool is full
     */
    public function add(Job $job)
    {
        foreach ($this->pool as $id => $item) {
            if (null === $item) {
                $this->pool[$id] = $job;
                ++$this->count;
                return $id;
            }
        }

        throw new \RuntimeException('Pool is full');
    }

    /**
     * @param Job $job
     * @return int Index where item was stored
     *
     * @throws \RuntimeException If job is not contained in pool
     */
    public function remove(Job $job)
    {
        foreach ($this->pool as $id => $item) {
            if ($job === $item) {
                $this->pool[$id] = null;
                --$this->count;
                return $id;
            }
        }

        throw new \RuntimeException('Pool does not contain item');
    }

    public function getIterator()
    {
        foreach ($this->pool as $id => $item) {
            if (null !== $item) {
                yield $item;
            }
        }
    }

    /**
     * Number of items in pool
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Pool max size
     *
     * @return int
     */
    public function getSize()
    {
        return $this->pool->getSize();
    }

    /**
     * If all pool slots are used
     *
     * @return bool
     */
    public function isFull()
    {
        return $this->count === $this->pool->getSize();
    }

    /**
     * If pool is empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return 0 === $this->count;
    }
}
