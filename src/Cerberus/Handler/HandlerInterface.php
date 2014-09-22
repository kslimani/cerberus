<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;
use Cerberus\ErrorHandler;

interface HandlerInterface
{
    public function setErrorHandler(ErrorHandler $errorHandler);

    public function setPriority($priority);

    public function getPriority();

    public function handle($type, $message, $file, $line, $extra);
}
