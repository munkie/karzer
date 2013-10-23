<?php

namespace Karzer\Util;

use Karzer\Framework\Exception;

class Stream
{
    const BLOCKING_MODE = 1;
    const NON_BLOCKING_MODE = 0;

    /**
     * @var resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $buffer = '';

    /**
     * @var int
     */
    protected $readLength = 8192;

    /**
     * @param resource $stream
     * @throws \Karzer\Framework\Exception
     */
    public function __construct($stream, $mode = self::BLOCKING_MODE)
    {
        if (!is_resource($stream)) {
            throw new Exception('Stream is not a resource');
        }

        $this->resource = $stream;

        $this->setBlocking($mode);
    }

    /**
     * @param int $mode
     */
    public function setBlocking($mode = self::NON_BLOCKING_MODE)
    {
        stream_set_blocking($this->resource, $mode);
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param resource $stream
     * @return bool
     */
    public function isEqualTo($stream)
    {
        return $this->resource === $stream;
    }

    /**
     * @param int $length
     * @return bool
     */
    public function read($length = null)
    {
        $length = ($length) ?: $this->readLength;
        $read = fread($this->resource, $length);
        if (feof($this->resource)) {
            $this->close();
        }
        if (false === $read) {
            return false;
        } else {
            $this->buffer.= $read;
            return true;
        }
    }

    public function close()
    {
        fclose($this->resource);
        $this->resource = null;
    }

    /**
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return $this->resource !== null;
    }

    /**
     * @return array
     */
    public function getMeta()
    {
        return stream_get_meta_data($this->resource);
    }
}
