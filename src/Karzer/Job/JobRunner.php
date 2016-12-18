<?php

namespace Karzer\Job;

use Karzer\Exception\RuntimeException;

class JobRunner
{
    /**
     * @var JobPool|Job[]
     */
    private $pool;

    /**
     * @var int|null stream_select timeout in usec
     */
    private $timeout;

    /**
     * @param JobPool $pool
     * @param int|null $timeout
     */
    public function __construct(JobPool $pool, $timeout = null)
    {
        $this->pool = $pool;
        $this->timeout = $timeout;
    }

    /**
     * Add test job to execution queue
     *
     * @param Job $job
     */
    public function enqueueJob(Job $job)
    {
        $this->pool->enqueue($job);
    }

    /**
     * Start job processing
     *
     * @throws \Karzer\Exception\RuntimeException
     */
    public function run()
    {
        $this->pool->fillPool();

        while (!$this->pool->isEmpty()) {
            if (!$this->processPoolStreams()) {
                return;
            }
        }
    }

    /**
     * Listen to pool streams and process jobs
     *
     * @return bool If processing should be continued
     * @throws \Karzer\Exception\RuntimeException When failed to read streams
     */
    private function processPoolStreams()
    {
        $streams = $this->waitStreams($this->pool->getStreams());

        foreach ($streams as $stream) {
            $job = $this->pool->getJobByStream($stream);
            if (!$this->processPoolJob($job, $stream)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Wait for update in given streams
     *
     * @param resource[] $read List of streams to listen
     * @return resource[] List of updated streams
     *
     * @throws \Karzer\Exception\RuntimeException If stream_select failed
     */
    private function waitStreams(array $read)
    {
        if (0 === count($read)) {
            return [];
        }

        $write = $except = [];

        $result = stream_select($read, $write, $except, null, $this->timeout);

        if (false === $result) {
            throw new RuntimeException('Stream select failed');
        }

        return $read;
    }

    /**
     * Read job stream and handle job close
     *
     * @param Job $job
     * @param resource $stream
     *
     * @return bool false - test execution should be stopped
     */
    private function processPoolJob(Job $job, $stream)
    {
        if ($job->readStream($stream)) {
            return true;
        }

        return !$this->pool->stop($job);
    }

}
