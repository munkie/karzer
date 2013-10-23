<?php

namespace Karzer\Framework\TestCase;

use Karzer\Framework\TextTemplateYield;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Karzer\Util\Job\Job;
use PHPUnit_Framework_TestResult;
use PHPUnit_Framework_TestCase;
use Text_Template;
use ReflectionProperty;

abstract class SymfonyWebTestCase extends WebTestCase implements JobTestInterface
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
     * @param PHPUnit_Framework_TestResult $result
     * @return Job
     */
    public function createJob(PHPUnit_Framework_TestResult $result)
    {
        try {
            $this->yieldTemplate = true;
            parent::run($result);
        } catch (TextTemplateYield $yield) {
            return new Job($yield->getTemplate(), $this, $result);
        }
    }

    /**
     * @param Text_Template $template
     * @throws TextTemplateYield
     */
    protected function prepareTemplate(Text_Template $template)
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
        $property = new ReflectionProperty('PHPUnit_Framework_TestCase', 'result');
        $property->setAccessible(true);
        $property->setValue($this, null);
    }

    /**
     * @return bool
     */
    public function useErrorHandler()
    {
        // XXX Dirty hack to get useErrorHandler property value
        $property = new ReflectionProperty('PHPUnit_Framework_TestCase', 'useErrorHandler');
        $property->setAccessible(true);
        $value = $property->getValue($this);
        return null !== $value;
    }

    /**
     * @return string
     */
    protected static function getPhpUnitXmlDir()
    {
        $oldScript = $_SERVER['argv'][0];
        // just to make shure parent check will proceed
        $_SERVER['argv'][0] = '/bin/phpunit';
        $dir = parent::getPhpUnitXmlDir();
        $_SERVER['argv'][0] = $oldScript;
        return $dir;
    }
}
