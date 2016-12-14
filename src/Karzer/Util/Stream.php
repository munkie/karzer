<?php

namespace Karzer\Util;

class Stream
{

    /**
     * Blocking modes
     */
    const BLOCKING_MODE = true;
    const NON_BLOCKING_MODE = false;

    /**
     * Default read length
     */
    const READ_LENGTH_DEFAULT = 8192;

    /**
     * Stream resource
     *
     * @var resource
     */
    private $resource;

    /**
     * Stream buffer content
     *
     * @var string
     */
    private $buffer = '';

    /**
     * @param resource $resource Stream resource
     * @param bool $blockingMode Stream blocking mode
     * @throws \InvalidArgumentException
     */
    public function __construct($resource, $blockingMode = self::BLOCKING_MODE)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Stream is not a resource');
        }

        $this->resource = $resource;
        stream_set_blocking($this->resource, $blockingMode);
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Does this stream represents given resource
     *
     * @param resource $resource
     * @return bool
     */
    public function isSameResource($resource)
    {
        return $this->resource === $resource;
    }

    /**
     * Read from stream
     *
     * @param int|null $length
     * @return bool
     */
    public function read($length = null)
    {
        $length = $length ?: self::READ_LENGTH_DEFAULT;
        $read = fread($this->resource, $length);
        if (feof($this->resource)) {
            $this->close();
        }
        if (false === $read) {
            return false;
        }

        $this->buffer.= $read;
        return true;
    }

    /**
     * Write to stream
     *
     * @param string $string
     * @return int
     */
    public function write($string)
    {
        return fwrite($this->resource, $string);
    }

    /**
     * Close stream
     */
    public function close()
    {
        fclose($this->resource);
        $this->resource = null;
    }

    /**
     * Get stream buffer
     *
     * @param bool $trim Trim buffer
     * @return string
     */
    public function getBuffer($trim = false)
    {
        return $trim ? trim($this->buffer) : $this->buffer;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return is_resource($this->resource);
    }

}
