<?php

namespace Karzer\Job;

use Karzer\Exception\SerializableException;
use Karzer\Karzer;
use Karzer\Util\TextTemplateYield;

trait JobTestTrait
{
    /**
     * @var int
     */
    protected $poolPosition;

    /**
     * @var bool
     */
    protected $yieldTemplate = false;

    /**
     * @param int $poolPosition
     */
    public function setPoolPosition($poolPosition)
    {
        if (null === $this->poolPosition) {
            $this->poolPosition = $poolPosition;
            Karzer::setThreadNumber($poolPosition);
        }
    }

    /**
     * @return int
     */
    public function getPoolPosition()
    {
        return $this->poolPosition;
    }

    /**
     * @return bool
     */
    public function runTestInSeparateProcess()
    {
        return $this->runTestInSeparateProcess;
    }

    /**
     * @param \PHPUnit_Framework_TestResult $result
     * @return Job
     */
    public function createJob(\PHPUnit_Framework_TestResult $result)
    {
        try {
            $this->yieldTemplate = true;
            $this->run($result);
        } catch (TextTemplateYield $yield) {
            return new Job($yield->getTemplate(), $this, $result);
        }
    }

    /**
     * @param \Text_Template $template
     * @throws TextTemplateYield
     */
    protected function prepareTemplate(\Text_Template $template)
    {
        if ($this->yieldTemplate) {
            $this->yieldTemplate = false;
            throw new TextTemplateYield($template);
        }
    }

    /**
     *
     */
    public function unsetTestResultObject()
    {
        // Dirty hack to unset private property
        $property = new \ReflectionProperty(\PHPUnit_Framework_TestCase::class, 'result');
        $property->setAccessible(true);
        $property->setValue($this, null);
    }

    /**
     * @return bool
     */
    public function useErrorHandler()
    {
        // XXX Dirty hack to get useErrorHandler private property value
        $property = new \ReflectionProperty(\PHPUnit_Framework_TestCase::class, 'useErrorHandler');
        $property->setAccessible(true);
        $value = $property->getValue($this);
        return null !== $value;
    }

    /**
     * @return bool
     */
    public function isInIsolation()
    {
        // XXX Dirty hack to get inIsolation private property value
        $property = new \ReflectionProperty(\PHPUnit_Framework_TestCase::class, 'inIsolation');
        $property->setAccessible(true);
        $value = $property->getValue($this);
        return (bool) $value;
    }

    /**
     * @param \Exception $e
     */
    protected function onNotSuccessfulTest($e)
    {
        if ($this->isInIsolation()) {
            $e = SerializableException::factory($e);
        }
        parent::onNotSuccessfulTest($e);
    }
}