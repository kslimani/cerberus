<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;
use Cerberus\ErrorHandler;

abstract class Handler implements HandlerInterface
{
    private $errorHandler;
    private $priority = -1;
    private $handleNonFatal = false;

    public function setErrorHandler(ErrorHandler $errorHandler)
    {
        if (!$errorHandler instanceof ErrorHandler) {
            throw new \InvalidArgumentException(
                "Argument to ".__METHOD__." must be an instance of Cerberus\\ErrorHandler"
            );
        }
        $this->errorHandler = $errorHandler;
    }

    protected function getErrorHandler()
    {
        return $this->errorHandler;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setHandleNonFatal($bool)
    {
        $this->handleNonFatal = ($bool === true);
    }

    public function getHandleNonFatal()
    {
        return $this->handleNonFatal;
    }

    public function canIgnoreError($type)
    {
        if (!$this->errorHandler) {
            throw new \Exception('Error handler must be set');
        }

        return (!$this->errorHandler->isFatal($type) && !$this->getHandleNonFatal());
    }

    public function getMemory($extra)
    {
        return (isset($extra['memory']) && is_numeric($extra['memory'])) ? $extra['memory'] : 0;
    }

    public function getTrace($extra)
    {
        if (isset($extra['trace'])) {
            return $extra['trace'];
        } elseif (isset($extra['exception']) && $extra['exception'] instanceof \Exception) {
            return $extra['exception']->getTrace();
        } else {
            return array();
        }
    }

}
