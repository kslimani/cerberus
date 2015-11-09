<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

class HandlerHeap extends \SplHeap
{
    protected function compare(HandlerInterface $handler1, HandlerInterface $handler2)
    {
        $p1 = $handler1->getPriority();
        $p2 = $handler2->getPriority();
        if ($p1 === $p2) {
            return 0;
        }

        return ($p1 < $p2) ? -1 : 1;
    }
}
