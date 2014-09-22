<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

class CliHandler extends Handler
{
    protected $callable;

    public function __construct($handleNonFatal = false)
    {
        $this->setHandleNonFatal($handleNonFatal);
    }

    public function handle($type, $message, $file, $line, $extra)
    {
        if ($this->canIgnoreError($type)) {
            return;
        }

        // TODO: [WIP] display error & trace in plain text format

        $msg = sprintf('%s: %s in %s line %s', $this->getDisplayName($extra), $message, $file, $line);
        $trace = $this->getTrace($extra);
        echo sprintf("Error : %s\n", $msg);
        // exit(1);
        return true;
    }

}
