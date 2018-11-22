<?php

namespace Websupport\YiiStreamLog;

/**
 * Stream log route inspired by Monolog's StreamHandler
 */
class StreamLogRoute extends \CLogRoute
{
    /** @var resource|null */
    private $stream;

    /** @var string|null */
    private $url;

    /** @var bool */
    public $useLocking;

    /**
     * @param resource|string $stream
     */
    public function setStream($stream)
    {
        if (is_resource($stream)) {
            $this->stream = $stream;
        } elseif (is_string($stream)) {
            $this->url = $stream;
        } else {
            throw new \InvalidArgumentException('A stream must either be a resource or a string.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function processLogs($logs)
    {
        foreach ($logs as $log) {
            $this->writeLogMessage($this->formatLogMessage($log[0], $log[1], $log[2], $log[3]));
        }

        if ($this->url && is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
    }

    /**
     * Write to stream
     * @param resource $stream
     * @param string $message
     */
    protected function streamWrite($stream, $message)
    {
        fwrite($stream, $message);
    }

    /**
     * Write log message
     * @param string $message
     */
    protected function writeLogMessage($message)
    {
        if (!is_resource($this->stream)) {
            $this->stream = fopen($this->url, 'a');

            if (!is_resource($this->stream)) {
                $this->stream = null;
                throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ', $this->url));
            }
        }

        if ($this->useLocking) {
            flock($this->stream, LOCK_EX);
        }

        $this->streamWrite($this->stream, $message);

        if ($this->useLocking) {
            flock($this->stream, LOCK_UN);
        }
    }
}
