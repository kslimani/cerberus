<?php

namespace Cerberus\Tests;

use Cerberus\ErrorHandler;
use Cerberus\Tests\Fixtures\MockHandler;

class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $eh;

    public function setUp()
    {
        $this->eh = new ErrorHandler();
    }

    public function testCustomConstructor()
    {
        $customErrorHandler = new ErrorHandler(false, true, true);
        $this->assertNotTrue($customErrorHandler->getDebug());
        $this->assertTrue($customErrorHandler->getThrowExceptions());
        $this->assertTrue($customErrorHandler->getThrowNonFatal());
    }

    public function testDefaultConstructor()
    {
        $this->assertTrue($this->eh->getDebug());
        $this->assertNotTrue($this->eh->getThrowExceptions());
        $this->assertNotTrue($this->eh->getThrowNonFatal());
    }

    public function testSetters()
    {
        $this->eh->setDebug(false)->setThrowExceptions(true)->setThrowNonFatal(true);
        $this->assertNotTrue($this->eh->getDebug());
        $this->assertTrue($this->eh->getThrowExceptions());
        $this->assertTrue($this->eh->getThrowNonFatal());
    }

    public function testNoHandler()
    {
        $handler = new MockHandler();
        $this->setExpectedException('Exception');
        $handler->canIgnoreError(0);
    }

    public function testHandlerExtra()
    {
        $handler = new MockHandler();
        $this->eh->addHandler($handler);

        $this->assertInstanceOf('Cerberus\ErrorHandler', $handler->getInternalErrorHandler());
        $this->assertEquals($this->eh, $handler->getInternalErrorHandler());

        // Ensure extra debug data exists in default debug mode
        $this->eh->onError(E_ERROR, 'Error message', 'file.php', 1337);
        $lastError = $handler->getLastHandledError();
        $this->assertArrayHasKey('extra', $lastError);
        $extra = $lastError['extra'];

        $this->assertGreaterThan(0, $handler->getMemory($extra));
        $this->assertNotEquals('E_UNKNOWN', $handler->getDisplayName($extra));
        $this->assertNotEmpty($handler->getTrace($extra));

        // Ensure extra debug data does NOT exists when debug is disabled
        $unknownPhpErrorType = -1337;
        $this->eh->setDebug(false);
        $handler->setHandleNonFatal(true); // -1337 is unknown and therefore non fatal
        $this->eh->onError($unknownPhpErrorType, 'Unknown error message', 'file.php', 123);
        $lastError = $handler->getLastHandledError();
        $this->assertArrayHasKey('extra', $lastError);
        $extra = $lastError['extra'];

        $this->assertEquals(0, $handler->getMemory($extra));
        $this->assertEquals('E_UNKNOWN', $handler->getDisplayName($extra));
        $this->assertEmpty($handler->getTrace($extra));

        // Ensure Exception backtrace exists with debug disabled
        try {
            throw new \Exception("Fatal error message");
        } catch (\Exception $exception) {
        }
        $this->eh->onException($exception);
        $lastError = $handler->getLastHandledError();
        $this->assertArrayHasKey('extra', $lastError);
        $extra = $lastError['extra'];

        $this->assertArrayNotHasKey('trace', $extra);
        $this->assertNotEmpty($handler->getTrace($extra));
    }

}
