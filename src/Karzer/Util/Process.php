<?php

namespace Karzer\Util;

use Karzer\Exception\ErrorException;
use Karzer\Exception\RuntimeException;

class Process
{
    /**
     * PHP execute command
     *
     * @var string
     */
    private $cmd;

    /**
     * PHP script to execute
     *
     * @var string
     */
    private $script;

    /**
     * Process resource
     *
     * @var resource
     */
    private $resource;

    /**
     * STDIN stream
     *
     * @var Stream
     */
    private $stdin;

    /**
     * STDOUT stream
     *
     * @var Stream
     */
    private $stdout;

    /**
     * STDERR stream
     *
     * @var Stream
     */
    private $stderr;

    /**
     * @param string $cmd PHP executable command
     * @param string $script PHP script to execute
     */
    public function __construct($cmd, $script)
    {
        $this->cmd = $cmd;
        $this->script = $script;
    }

    /**
     * @param string $script
     * @param array $settings
     * @param string|null $file
     *
     * @return static
     */
    public static function createPhpProcess($script, array $settings = [], $file = null)
    {
        $cmd = (new \PHPUnit_Util_PHP_Default())->getCommand($settings, $file);

        return new static($cmd, $script);
    }

    /**
     * @param array $customEnv
     * @return array
     */
    private function getEnv(array $customEnv = [])
    {
        $env = isset($_SERVER) ? $_SERVER : [];
        unset($env['argv'], $env['argc']);
        $env = array_merge($env, $customEnv);

        foreach ($env as $envKey => $envVar) {
            if (is_array($envVar)) {
                unset($env[$envKey]);
            }
        }

        return $env;
    }

    /**
     * Open process
     *
     * @param array $customEnv Custom ENV params to pass to process
     * @throws RuntimeException If proc_open command failed
     */
    public function open(array $customEnv = [])
    {
        ErrorException::setHandler();

        try {
            $this->resource = proc_open(
                $this->cmd,
                [
                    0 => ['pipe', 'r'],
                    1 => ['pipe', 'w'],
                    2 => ['pipe', 'w']
                ],
                $pipes,
                null,
                $this->getEnv($customEnv)
            );
        } catch (ErrorException $exception) {
            throw RuntimeException::forkFailed($exception, $this->cmd);
        } finally {
            ErrorException::restoreHandler();
        }

        $this->stdin  = new Stream($pipes[0]);
        $this->stdout = new Stream($pipes[1], Stream::NON_BLOCKING_MODE);
        $this->stderr = new Stream($pipes[2], Stream::NON_BLOCKING_MODE);
    }

    /**
     * Close process
     *
     * @return int
     */
    public function close()
    {
        return proc_close($this->resource);
    }

    /**
     * Open process and write php script to STDIN
     *
     * @param array $customEnv Custom ENV vars to pass to php script
     *
     * @throws RuntimeException When process open failed
     */
    public function start(array $customEnv = [])
    {
        $this->open($customEnv);
        $this->stdin->write($this->script);
        $this->stdin->close();
    }

    /**
     * Get STDERR stream
     *
     * @return Stream
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * Get STDOUT stream
     *
     * @return Stream
     */
    public function getStdout()
    {
        return $this->stdout;
    }
}
