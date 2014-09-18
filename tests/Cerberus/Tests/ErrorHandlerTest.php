<?php

namespace Cerberus\Tests;

use Cerberus\ErrorHandler;

class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultConstructor()
    {
        $eh = new ErrorHandler();
        $this->assertTrue($eh->getDebug());
        $this->assertNotTrue($eh->getThrowExceptions());
        $this->assertNotTrue($eh->getThrowNonFatal());
    }

    public function testCustomConstructor()
    {
        $eh = new ErrorHandler(false, true, true);
        $this->assertNotTrue($eh->getDebug());
        $this->assertTrue($eh->getThrowExceptions());
        $this->assertTrue($eh->getThrowNonFatal());
    }

    public function testSetters()
    {
        $eh = new ErrorHandler();
        $eh->setDebug(false)->setThrowExceptions(true)->setThrowNonFatal(true);
        $this->assertNotTrue($eh->getDebug());
        $this->assertTrue($eh->getThrowExceptions());
        $this->assertTrue($eh->getThrowNonFatal());
    }
}
