<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

class CallableHandler extends Handler
{
    protected $callable;

    public function __construct($callable, $handleNonFatal = false)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException('Argument to '.__METHOD__.' must be valid callable');
        }
        $this->callable = $callable;
        $this->setHandleNonFatal($handleNonFatal);
    }

    public function handle($type, $displayType, $message, $file, $line, $extra)
    {
        if ($this->canIgnoreError($type)) {
            return;
        }

        if (!isset($extra['exception'])) {
            $extra += array(
                'displayType' => $displayType,
                'file' => $file,
                'line' => $line,
                'message' => $message,
                'type' => $type
            );
        }

        return call_user_func(
            $this->callable,
            sprintf('%s: %s in %s line %s', $displayType, $message, $file, $line),
            $extra
        );
    }

}
