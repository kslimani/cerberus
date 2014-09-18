<?php

namespace Cerberus\Tests\Fixtures;

class MockError
{
    protected $displayType;
    protected $type;
    protected $message;
    protected $file;
    protected $line;
    protected $handled;

    public function __construct($displayType, $type, $message, $file, $line)
    {
        $this->displayType = $displayType;
        $this->type = $type;
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->setHandled(false);
    }

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
        return $this->displayType;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getLine()
    {
        return $this->line;
    }
}
