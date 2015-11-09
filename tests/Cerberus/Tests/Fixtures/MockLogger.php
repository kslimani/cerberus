<?php

namespace Cerberus\Tests\Fixtures;

use Psr\Log\AbstractLogger;

class MockLogger extends AbstractLogger
{
    protected $lines = array();
    protected $lineCount = 0;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        ++$this->lineCount;
        $this->lines[$this->lineCount] = array(
            'level' => $level,
            'message' => $message,
            'context' => $context,
        );
    }

    public function getLines()
    {
        return $this->lines;
    }

    public function getLine($lineNumber)
    {
        return isset($this->lines[$lineNumber]) ? $this->lines[$lineNumber] : null;
    }

    public function getLineCount()
    {
        return $this->lineCount;
    }

    public function clear()
    {
        $this->lines = array();
        $this->lineCount = 0;

        return $this;
    }
}
