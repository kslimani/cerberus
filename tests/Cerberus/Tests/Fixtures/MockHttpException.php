<?php

namespace Cerberus\Tests\Fixtures;

use Symfony\Component\HttpKernel\Exception\HttpException;

class MockHttpException extends HttpException
{
    protected $handled = false;

    public function getHandled()
    {
        return $this->handled;
    }

    public function setHandled($bool)
    {
        $this->handled = $bool;
    }

    public function getDisplayType()
    {
        return get_class($this);
    }
}
