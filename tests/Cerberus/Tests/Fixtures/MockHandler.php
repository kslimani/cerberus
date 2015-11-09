<?php

namespace Cerberus\Tests\Fixtures;

use Cerberus\Handler\Handler;

class MockHandler extends Handler
{
    protected $lastHandledError;

    public function __construct()
    {
        $this->lastHandledError = null;
    }

    public function handle($type, $message, $file, $line, $extra)
    {
        if ($this->canIgnoreError($type)) {
            return;
        }

        $this->lastHandledError = array(
            'type' => $type,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'extra' => $extra,
        );

        return false; // Delegate to next handler
    }

    public function getLastHandledError()
    {
        return $this->lastHandledError;
    }

    public function getInternalErrorHandler()
    {
        return $this->getErrorHandler();
    }
}
