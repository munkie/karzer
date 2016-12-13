<?php
namespace Karzer\Framework\TestCase;

use Karzer\Util\Job\Job;

interface JobTestInterface extends \PHPUnit_Framework_Test
{
    /**
     * @param \PHPUnit_Framework_TestResult $result
     * @return Job
     */
    public function createJob(\PHPUnit_Framework_TestResult $result);

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
