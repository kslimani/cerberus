<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

class HandlerHeap extends \SplHeap
{

    protected function compare($handler1, $handler2)
    {
        // if (!$handler1 instanceof HandlerInterface || !$handler2 instanceof HandlerInterface) {
        //     throw new \InvalidArgumentException(
        //         "Arguments to " . __METHOD__ . " must be instances of Cerberus\\Handler\\HandlerInterface"
        //     );
        // }

        // Expect instances of HandlerInterface

        $p1 = $handler1->getPriority();
        $p2 = $handler2->getPriority();
        if ($p1 === $p2) return 0;
        return ($p1 < $p2) ? -1 : 1;
    }

}
