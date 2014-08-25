<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

use Cerberus\ErrorHandler;

class HandlerList extends \SplDoublyLinkedList
{
    protected $errorHandler;
    protected $priority;
    protected $heap;

    public function __construct(ErrorHandler $errorHandler, $defaultPriority = 10)
    {
        if (!$errorHandler instanceof ErrorHandler) {
            throw new \InvalidArgumentException(
                "Argument to ".__METHOD__." must be an instance of Cerberus\\ErrorHandler"
            );
        }

        $this->errorHandler = $errorHandler;
        $this->priority = $defaultPriority;
        $this->heap = new HandlerHeap();
        $this->setIteratorMode(\SplDoublyLinkedList::IT_MODE_LIFO);
    }

    public function addHandler(HandlerInterface $handler)
    {
        if (!$handler instanceof HandlerInterface) {
            throw new \InvalidArgumentException(
                "Argument to ".__METHOD__." must be an instance of Cerberus\\Handler\\HandlerInterface"
            );
        }

        $handler->setErrorHandler($this->errorHandler);
        if (-1 === $handler->getPriority()) {
            $handler->setPriority($this->priority);
            $this->priority++;
        }

        $this->push($handler);
        while (! $this->isEmpty()) {
            $this->heap->insert($this->shift());
        }
        while (! $this->heap->isEmpty()) {
            $this->unshift($this->heap->extract());
        }
    }
}
