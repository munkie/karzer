<?php

namespace Karzer\PHPUnit\Util\Log;

class Teamcity extends \PHPUnit_Util_Log_TeamCity
{
    const REGEX_PATTERN = '/flowId=\'.+?\'';

    /**
     * @var string
     */
    private $flowId;

    /**
     * {@inheritdoc}
     */
    public function write($buffer)
    {
        $buffer = $this->replaceFlowId($buffer);
        parent::write($buffer);
    }

    public function startTest(\PHPUnit_Framework_Test $test)
    {
        parent::startTest($test);
    }

    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        parent::endTest($test, $time);
    }

    private function updateFlowId(\PHPUnit_Framework_Test $test)
    {
        if ($test instanceof \PHPUnit_Framework_TestCase) {
            $result = $test->getTestResultObject();
        }
    }

    /**
     * @param string $buffer
     * @return string
     */
    private function replaceFlowId($buffer)
    {
        if (1 === preg_match(self::REGEX_PATTERN, $buffer)) {
            $buffer = preg_replace(self::REGEX_PATTERN, "flowId='{$this->flowId}'", $buffer);
        }
        return $buffer;
    }

}
