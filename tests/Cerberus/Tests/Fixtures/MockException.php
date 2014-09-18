<?php

namespace Cerberus\Tests\Fixtures;

class MockException extends \Exception
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
