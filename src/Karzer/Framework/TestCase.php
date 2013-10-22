<?php

namespace Karzer\Framework;

use Karzer\Util\Job\Job;
use PHPUnit_Framework_TestResult;
use PHPUnit_Framework_TestCase;
use Text_Template;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var bool
     */
    protected $yieldTemplate = false;

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
        $this->yieldTemplate = false;
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
        $reflection = new \ReflectionProperty($this, 'result');
        $reflection->setAccessible(true);
        $reflection->setValue($this, null);
    }

    /**
     * @return bool
     */
    public function useErrorHandler()
    {
        // Dirty hack to unset private property
        $reflection = new \ReflectionProperty($this, 'useErrorHandler');
        $reflection->setAccessible(true);
        $value = $reflection->getValue($this);
        return null !== $value;
    }
}
