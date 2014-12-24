<?php

namespace Karzer\Framework;

use Karzer\Framework\TestCase\JobTestInterface;
use Karzer\Util\Job\Job;
use PHPUnit_Framework_TestResult;

class TestDecorator extends \PHPUnit_Extensions_TestDecorator implements JobTestInterface
{
    /**
     * The Test to be decorated.
     *
     * @var \PHPUnit_Framework_TestCase
     */
    protected $test;

    /**
     * @var int
     */
    protected $poolPosition;

    /**
     * @var string
     */
    protected $templateFile;

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, array $args = array())
    {
        return call_user_func_array(array($this->test, $name), $args);
    }

    /**
     * Copy-paste of test init code from TestCase::run method
     *
     * @param PHPUnit_Framework_TestResult $result
     */
    public function init(\PHPUnit_Framework_TestResult $result = null)
    {
        if ($result === null) {
            $result = $this->createResult();
        }

        if (!$this->test instanceof \PHPUnit_Framework_Warning) {
            $this->test->setTestResultObject($result);
            $this->callPrivateMethod('setUseErrorHandlerFromAnnotation');
        }

        /* This code will be executed in job
        if ($this->useErrorHandler()) {
            $oldErrorHandlerSetting = $result->getConvertErrorsToExceptions();
            $result->convertErrorsToExceptions($this->useErrorHandler);
        }
        */

        /*
         * This code will never evaluate for isolated test
         *
        if (!$this instanceof \PHPUnit_Framework_Warning && !$this->test->handleDependencies()) {
            return;
        }
        */
    }

    /**
     * Copy-paste of template create code from TestCase run method
     *
     * @return \Text_Template
     */
    public function createTemplate()
    {
        $result = $this->test->getTestResultObject();

        $class = new \ReflectionClass($this->test);

        $template = new \Text_Template(
            $this->getTemplateFile()
        );

        if ($this->getTestPrivateProperty('preserveGlobalState')) {
            $constants     = \PHPUnit_Util_GlobalState::getConstantsAsString();
            $globals       = \PHPUnit_Util_GlobalState::getGlobalsAsString();
            $includedFiles = \PHPUnit_Util_GlobalState::getIncludedFilesAsString();
            $iniSettings   = \PHPUnit_Util_GlobalState::getIniSettingsAsString();
        } else {
            $constants     = '';
            if (!empty($GLOBALS['__PHPUNIT_BOOTSTRAP'])) {
                $globals     = '$GLOBALS[\'__PHPUNIT_BOOTSTRAP\'] = ' . var_export($GLOBALS['__PHPUNIT_BOOTSTRAP'], true) . ";\n";
            } else {
                $globals     = '';
            }
            $includedFiles = '';
            $iniSettings   = '';
        }

        //
        $globals = $this->tweakGlobals($globals);
        //

        $coverage                                = $result->getCollectCodeCoverageInformation()       ? 'true' : 'false';
        $isStrictAboutTestsThatDoNotTestAnything = $result->isStrictAboutTestsThatDoNotTestAnything() ? 'true' : 'false';
        $isStrictAboutOutputDuringTests          = $result->isStrictAboutOutputDuringTests()          ? 'true' : 'false';
        $isStrictAboutTestSize                   = $result->isStrictAboutTestSize()                   ? 'true' : 'false';
        $isStrictAboutTodoAnnotatedTests         = $result->isStrictAboutTodoAnnotatedTests()         ? 'true' : 'false';

        if (defined('PHPUNIT_COMPOSER_INSTALL')) {
            $composerAutoload = var_export(PHPUNIT_COMPOSER_INSTALL, true);
        } else {
            $composerAutoload = '\'\'';
        }

        if (defined('__PHPUNIT_PHAR__')) {
            $phar = var_export(__PHPUNIT_PHAR__, true);
        } else {
            $phar = '\'\'';
        }

        $data            = var_export(serialize($this->getTestPrivateProperty('data')), true);
        $dataName        = var_export($this->getTestPrivateProperty('dataName'), true);
        $dependencyInput = var_export(serialize($this->getTestPrivateProperty('dependencyInput')), true);
        $includePath     = var_export(get_include_path(), true);
        // must do these fixes because TestCaseMethod.tpl has unserialize('{data}') in it, and we can't break BC
        // the lines above used to use addcslashes() rather than var_export(), which breaks null byte escape sequences
        $data            = "'." . $data . ".'";
        $dataName        = "'.(" . $dataName . ").'";
        $dependencyInput = "'." . $dependencyInput . ".'";
        $includePath     = "'." . $includePath . ".'";

        $template->setVar(
            array(
                'composerAutoload'                        => $composerAutoload,
                'phar'                                    => $phar,
                'filename'                                => $class->getFileName(),
                'className'                               => $class->getName(),
                'methodName'                              => $this->test->getName(false),
                'collectCodeCoverageInformation'          => $coverage,
                'data'                                    => $data,
                'dataName'                                => $dataName,
                'dependencyInput'                         => $dependencyInput,
                'constants'                               => $constants,
                'globals'                                 => $globals,
                'include_path'                            => $includePath,
                'included_files'                          => $includedFiles,
                'iniSettings'                             => $iniSettings,
                'isStrictAboutTestsThatDoNotTestAnything' => $isStrictAboutTestsThatDoNotTestAnything,
                'isStrictAboutOutputDuringTests'          => $isStrictAboutOutputDuringTests,
                'isStrictAboutTestSize'                   => $isStrictAboutTestSize,
                'isStrictAboutTodoAnnotatedTests'         => $isStrictAboutTodoAnnotatedTests
            )
        );

        return $template;
    }

    /**
     * @return string
     */
    protected function getTemplateFile()
    {
        if (null === $this->templateFile) {
            $reflection = new \ReflectionClass('PHPUnit_Framework_TestCase');
            $testCaseDir = dirname($reflection->getFileName());
            $this->templateFile = $testCaseDir . '/../Util/PHP/Template/TestCaseMethod.tpl';
        }
        return $this->templateFile;
    }

    /**
     * @param PHPUnit_Framework_TestResult $result
     * @return Job
     */
    public function createJob(PHPUnit_Framework_TestResult $result)
    {
        return new Job($this->createTemplate(), $this, $this->test->getTestResultObject());
    }

    /**
     * @return \PHPUnit_Framework_TestCase
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * @param int $poolPosition
     */
    public function setPoolPosition($poolPosition)
    {
        $this->poolPosition = $poolPosition;
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
        return $this->getTestPrivateProperty('runTestInSeparateProcess');
    }

    /**
     *
     */
    public function unsetTestResultObject()
    {
        $this->getTestPrivatePropertyReflection('result')->setValue($this->test, null);
    }

    /**
     * @return bool
     */
    public function useErrorHandler()
    {
        return null !== $this->getTestPrivateProperty('useErrorHandler');
    }

    /**
     * @param string $propertyName
     * @return mixed
     */
    protected function getTestPrivateProperty($propertyName)
    {
        return $this->getTestPrivatePropertyReflection($propertyName)->getValue($this->test);
    }

    /**
     * @param string $propertyName
     * @return \ReflectionProperty
     */
    protected function getTestPrivatePropertyReflection($propertyName)
    {
        $property = new \ReflectionProperty('PHPUnit_Framework_TestCase', $propertyName);
        $property->setAccessible(true);
        return $property;
    }

    /**
     * @param string $methodName
     * @param array $args
     */
    protected function callPrivateMethod($methodName, array $args = array())
    {
        $method = new \ReflectionMethod('PHPUnit_Framework_TestCase', $methodName);
        $method->setAccessible(true);
        $method->invokeArgs($this->test, $args);
    }

    /**
     * @param string $globals
     * @return string
     */
    protected function tweakGlobals($globals)
    {
        $globals .= $this->createGlobalString('_SERVER', 'KARZER_THREAD', $this->getPoolPosition());
        // override script path in argv to avoid
        // RuntimeException('You must override the KernelTestCase::createKernel() method.')
        // in Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::getPhpUnitXmlDir()
        $argv = $_SERVER['argv'];
        $argv[0] = preg_replace('/karzer$/', 'phpunit', $argv[0]);
        $globals.= $this->createGlobalString('_SERVER', 'argv', $argv);
        $globals.= $this->createGlobalString(null, 'argv', $argv);

        return $globals;
    }

    /**
     * @param string $globalArray
     * @param string $key
     * @param mixed $value
     * @return string
     */
    protected function createGlobalString($globalArray, $key, $value)
    {
        $pattern = (null !== $globalArray) ? '$GLOBALS[\'%3$s\'][\'%1$s\'] = %2$s;' : '$GLOBALS[\'%1$s\'] = %2$s;';
        return sprintf(
            $pattern . "\n",
            $key,
            var_export($value, true),
            $globalArray
        );
    }
}
