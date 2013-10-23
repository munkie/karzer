<?php

namespace Karzer\Util\Job;

use Karzer\Framework\Exception;
use Karzer\Util\Stream;
use PHPUnit_Util_PHP_Default;
use PHPUnit_Framework_Exception;

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
     * @param int $threads
     */
    public function __construct($threads)
    {
        $this->pool = new JobPool($threads);
    }

    /**
     * @param Job $job
     * @throws \PHPUnit_Framework_Exception
     */
    public function startJob(Job $job)
    {
        $this->pool->add($job);

        $process = proc_open(
            $this->getPhpBinary(),
            array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w')
            ),
            $pipes
        );

        if (!is_resource($process)) {
            throw new PHPUnit_Framework_Exception(
                'Unable to create process for process isolation.'
            );
        }

        $job->setProcess($process);

        $job->startTest();

        $this->process($pipes[0], $job->render());
        fclose($pipes[0]);

        $stdout = new Stream($pipes[1], Stream::NON_BLOCKING_MODE);
        $stderr = new Stream($pipes[2], Stream::NON_BLOCKING_MODE);

        $job->setStdout($stdout);
        $job->setStderr($stderr);
    }

    /**
     * @param Job $job
     */
    public function stopJob(Job $job)
    {
        $this->pool->remove($job);

        proc_close($job->getProcess());

        $this->processChildResult(
            $job->getTest(),
            $job->getResult(),
            $job->getStdout()->getBuffer(),
            $job->getStderr()->getBuffer()
        );

        $job->endTest();
    }

    /**
     * @param Job $job
     * @return Job[]|bool
     * @throws \Karzer\Framework\Exception
     */
    public function run(Job $job = null)
    {
        if ($job) {
            $this->startJob($job);
        }

        if ($this->pool->isEmpty()) {
            return false;
        }

        $processedJobs = array();
        do {
            $r = $this->pool->getStreams();

            $result = stream_select($r, $w = null, $x = null, $this->timeout);

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
                        }
                    }
                }
            }
        } while ($this->pool->isFull());

        return $processedJobs;
    }

    /**
     * @return Job[]
     */
    public function finishRun()
    {
        $jobs = array();
        while (false !== ($processedJobs = $this->run())) {
            $jobs = array_merge($processedJobs, $jobs);
        }
        return $jobs;
    }
}
