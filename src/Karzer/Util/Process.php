<?php

namespace Karzer\Util;

use Karzer\Exception\ErrorException;
use Karzer\Exception\ForkException;

class Process
{
    /**
     * @var string
     */
    protected $cmd;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var Stream
     */
    protected $stdin;

    /**
     * @var Stream
     */
    protected $stdout;

    /**
     * @var Stream
     */
    protected $stderr;

    /**
     * @param string $cmd
     */
    public function __construct($cmd)
    {
        $this->cmd = $cmd;
    }

    public function open()
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
                $pipes
            );
            ErrorException::restoreHandler();
        } catch (ErrorException $e) {
            ErrorException::restoreHandler();
            throw new ForkException('Failed to fork process', 0, $e);
        }

        $this->stdin  = new Stream($pipes[0]);
        $this->stdout = new Stream($pipes[1], Stream::NON_BLOCKING_MODE);
        $this->stderr = new Stream($pipes[2], Stream::NON_BLOCKING_MODE);
    }

    /**
     * @return int
     */
    public function close()
    {
        return proc_close($this->resource);
    }

    /**
     * Write PHP script to run test to process
     *
     * @param string $script
     */
    public function writeScript($script)
    {
        $this->getStdin()->write($script);
        $this->getStdin()->close();
    }

    /**
     * @return Stream
     */
    public function getStderr()
    {
        return $this->stderr;
    }

    /**
     * @return Stream
     */
    public function getStdin()
    {
        return $this->stdin;
    }

    /**
     * @return Stream
     */
    public function getStdout()
    {
        return $this->stdout;
    }
}
