<?php

namespace Karzer\Util\Job;

use Karzer\Framework\Exception;
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
     *
     */
    public function __construct()
    {
        $this->pool = new JobPool(3);
    }

    /**
     * @param Job $job
     * @throws \PHPUnit_Framework_Exception
     */
    public function startJob(Job $job)
    {
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

        $stdout = new Stream($pipes[1]);
        $stdout->setBlocking(Stream::NON_BLOCKING_MODE);

        $stderr = new Stream($pipes[2]);
        $stderr->setBlocking(Stream::NON_BLOCKING_MODE);

        $job->setStdout($stdout);
        $job->setStderr($stderr);

        $this->pool->add($job);
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
     * @return Job[]|bool
     * @throws \Karzer\Framework\Exception
     */
    public function getNext()
    {
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
        } while (count($processedJobs) == 0);

        return $processedJobs;
    }
}
