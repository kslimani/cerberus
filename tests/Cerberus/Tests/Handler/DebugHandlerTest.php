<?php

namespace Cerberus\Tests\Handler;

use Cerberus\Tests\HandlerTestCase;
use Cerberus\Tests\Fixtures\MockException;
use Cerberus\Handler\DebugHandler;

class DebugHandlerTest extends HandlerTestCase
{
    public function testConstructor()
    {
        $debugHandler = new DebugHandler();
        $this->eh->addHandler($debugHandler);
        $this->assertNotTrue($debugHandler->getHandleNonFatal());

        $debugHandler = new DebugHandler(true);
        $this->eh->addHandler($debugHandler);
        $this->assertTrue($debugHandler->getHandleNonFatal());
    }

    public function testGetterAndSetters()
    {
        $debugHandler = new DebugHandler();
        $this->assertNotEmpty($debugHandler->getVersion());
        $this->assertEquals('utf-8', $debugHandler->getCharset());
        $this->assertEquals(4096, $debugHandler->getMaxArgDisplaySize());

        $debugHandler->setCharset('iso-8859-1');
        $debugHandler->setMaxArgDisplaySize(2048);
        $this->assertEquals('iso-8859-1', $debugHandler->getCharset());
        $this->assertEquals(2048, $debugHandler->getMaxArgDisplaySize());
    }

    public function testHandleError()
    {
        $debugHandler = new DebugHandler();
        $this->eh->addHandler($debugHandler);

        $error = $this->createError('E_NOTICE', E_NOTICE, 'Error Message', 'file.php', 5);
        $result = $this->handleError($error);

        // DebugHandler abort and return false in PHP CLI
        $this->assertNotTrue($result);
    }

    public function testHandleException()
    {
        $debugHandler = new DebugHandler();
        $this->eh->addHandler($debugHandler);

        $exception = $this->createException(new MockException("Exception message"));
        $result = $this->handleException($exception);

        // DebugHandler abort and return false in PHP CLI
        $this->assertNotTrue($result);
    }
}
