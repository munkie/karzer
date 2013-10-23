<?php
namespace Karzer\Framework;

use Karzer\Util\Job\Job;
use PHPUnit_Framework_TestResult;

interface JobTestInterface
{
    /**
     * @param PHPUnit_Framework_TestResult $result
     * @return Job
     */
    public function createJob(PHPUnit_Framework_TestResult $result);

    /**
     * @param int $poolPosition
     */
    public function setPoolPosition($poolPosition);

    /**
     * @return bool
     */
    public function runTestInSeparateProcess();

    /**
     *
     */
    public function unsetTestResultObject();

    /**
     * @return int
     */
    public function getPoolPosition();

    /**
     * @return bool
     */
    public function useErrorHandler();
}