<?php

namespace Cerberus\Tests\Handler;

use Cerberus\Tests\HandlerTestCase;
use Cerberus\Tests\Fixtures\MockException;
use Cerberus\Handler\CallableHandler;

class CallableHandlerTest extends HandlerTestCase
{
    public function testBadArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        $handler = new CallableHandler('BAD.ARGUMENT');
    }

    public function testDisable()
    {
        $error = $this->createError('E_ERROR', E_ERROR, 'Error Message', 'file.php', 5);
        $phpunit = $this;

        $this->eh->Disable();

        $this->eh->addHandler(function ($message, $extra) use ($phpunit, $error) {
            $error->setHandled(true);
            $this->fail('Disable error handler failed');
        });

        $this->handleError($error);
        $this->assertNotTrue($error->getHandled());
    }

    public function testIgnoreNonFatalErrors()
    {
        $phpunit = $this;

        $this->eh->addHandler(function ($message, $extra) use ($phpunit) {
            $phpunit->fail('Non fatal error handled');
        });

        $errors = array(
            $this->createError('E_NOTICE', E_NOTICE, 'Error Message', 'file.php', 5),
            $this->createError('E_WARNING', E_WARNING, 'Error Message', 'file.php', 5),
            $this->createError('E_USER_NOTICE', E_USER_NOTICE, 'Error Message', 'file.php', 5),
            $this->createError('E_USER_WARNING', E_USER_WARNING, 'Error Message', 'file.php', 5),
        );

        foreach ($errors as $error) {
            $this->handleError($error);
        }
    }

    public function testHandleNonFatalError()
    {
        $error = $this->createError('E_NOTICE', E_NOTICE, 'Error Message', 'file.php', 5);

        $this->eh->addHandler(function ($message, $extra) use ($error) {

            $this->assertArrayHasKey('displayType', $extra);
            $this->assertArrayHasKey('file', $extra);
            $this->assertArrayHasKey('line', $extra);
            $this->assertArrayHasKey('message', $extra);
            $this->assertArrayHasKey('type', $extra);
            $this->assertArrayHasKey('trace', $extra);
            $this->assertArrayHasKey('memory', $extra);

            $this->assertEquals($error->getDisplayType(), $extra['displayType']);
            $this->assertEquals($error->getType(), $extra['type']);
            $this->assertEquals($error->getMessage(), $extra['message']);
            $this->assertEquals($error->getFile(), $extra['file']);
            $this->assertEquals($error->getLine(), $extra['line']);

            $error->setHandled(true);
        }, true);

        $this->handleError($error);
        $this->assertTrue($error->getHandled());
    }

    public function testHandleException()
    {
        $exception = $this->createException(new MockException("Exception message"));

        $this->eh->addHandler(function ($message, $extra) use ($exception) {

            $this->assertArrayHasKey('displayType', $extra);
            $this->assertArrayNotHasKey('file', $extra);
            $this->assertArrayNotHasKey('line', $extra);
            $this->assertArrayNotHasKey('message', $extra);
            $this->assertArrayNotHasKey('type', $extra);
            $this->assertArrayHasKey('exception', $extra);
            $this->assertArrayHasKey('memory', $extra);

            $e = $extra['exception'];

            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals($exception->getDisplayType(), get_class($e));
            $this->assertEquals($exception->getDisplayType(), $extra['displayType']);
            $this->assertEquals($exception->getCode(), $e->getCode());
            $this->assertEquals($exception->getMessage(), $e->getMessage());
            $this->assertEquals($exception->getFile(), $e->getFile());
            $this->assertEquals($exception->getLine(), $e->getLine());

            $exception->setHandled(true);
        }, true);

        $this->handleException($exception);
        $this->assertTrue($exception->getHandled());
    }

    public function testHandleErrorException()
    {
        $error = $this->createError('E_ERROR', E_ERROR, 'Error Message', 'file.php', 5);

        $this->eh->setThrowExceptions(true);

        $this->eh->addHandler(function ($message, $extra) use ($error) {

            $this->assertArrayHasKey('displayType', $extra);
            $this->assertArrayNotHasKey('file', $extra);
            $this->assertArrayNotHasKey('line', $extra);
            $this->assertArrayNotHasKey('message', $extra);
            $this->assertArrayNotHasKey('type', $extra);
            $this->assertArrayHasKey('exception', $extra);
            $this->assertArrayHasKey('memory', $extra);

            $e = $extra['exception'];

            $this->assertInstanceOf('ErrorException', $e);
            $expectedDisplayType = sprintf("%s (%s)", get_class($e), $error->getDisplayType());
            $this->assertEquals($expectedDisplayType, $extra['displayType']);
            $this->assertEquals($error->getType(), $e->getSeverity());
            $this->assertEquals($error->getMessage(), $e->getMessage());
            $this->assertEquals($error->getFile(), $e->getFile());
            $this->assertEquals($error->getLine(), $e->getLine());

            $error->setHandled(true);
        });

        // Catch and handle converted exception
        try {
            $this->handleError($error);
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        $this->assertTrue($error->getHandled());
    }

    public function testHandleContextError()
    {
        $error = $this->createError('E_ERROR', E_ERROR, 'Error Message', 'file.php', 5, $this->createContext());

        $this->eh->addHandler(function ($message, $extra) use ($error) {

            $this->assertArrayHasKey('displayType', $extra);
            $this->assertArrayHasKey('file', $extra);
            $this->assertArrayHasKey('line', $extra);
            $this->assertArrayHasKey('message', $extra);
            $this->assertArrayHasKey('type', $extra);
            $this->assertArrayHasKey('context', $extra);
            $this->assertArrayHasKey('trace', $extra);
            $this->assertArrayHasKey('memory', $extra);

            $this->assertEquals($error->getDisplayType(), $extra['displayType']);
            $this->assertEquals($error->getType(), $extra['type']);
            $this->assertEquals($error->getMessage(), $extra['message']);
            $this->assertEquals($error->getFile(), $extra['file']);
            $this->assertEquals($error->getLine(), $extra['line']);
            $this->assertEquals($error->getContext(), $extra['context']);

            $error->setHandled(true);
        });

        $this->handleError($error);
        $this->assertTrue($error->getHandled());
    }

    public function testHandleContextErrorException()
    {
        $error = $this->createError('E_ERROR', E_ERROR, 'Error Message', 'file.php', 5, $this->createContext());

        $this->eh->setThrowExceptions(true);

        $this->eh->addHandler(function ($message, $extra) use ($error) {

            $this->assertArrayHasKey('displayType', $extra);
            $this->assertArrayNotHasKey('file', $extra);
            $this->assertArrayNotHasKey('line', $extra);
            $this->assertArrayNotHasKey('message', $extra);
            $this->assertArrayNotHasKey('type', $extra);
            $this->assertArrayHasKey('exception', $extra);
            $this->assertArrayHasKey('memory', $extra);

            $e = $extra['exception'];

            $this->assertInstanceOf('Cerberus\Exception\ContextErrorException', $e);
            $this->assertEquals($error->getContext(), $e->getContext());
            $expectedDisplayType = sprintf("%s (%s)", get_class($e), $error->getDisplayType());
            $this->assertEquals($expectedDisplayType, $extra['displayType']);
            $this->assertEquals($error->getType(), $e->getSeverity());
            $this->assertEquals($error->getMessage(), $e->getMessage());
            $this->assertEquals($error->getFile(), $e->getFile());
            $this->assertEquals($error->getLine(), $e->getLine());

            $error->setHandled(true);
        });

        // Catch and handle converted exception
        try {
            $this->handleError($error);
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        $this->assertTrue($error->getHandled());
    }

    public function testHandleNoDebugError()
    {
        $error = $this->createError('E_ERROR', E_ERROR, 'Error Message', 'file.php', 5);

        $this->eh->setDebug(false);

        $this->eh->addHandler(function ($message, $extra) use ($error) {

            $this->assertArrayNotHasKey('trace', $extra);
            $this->assertArrayNotHasKey('memory', $extra);

            $error->setHandled(true);
        });

        $this->handleError($error);
        $this->assertTrue($error->getHandled());
    }

    public function testHandleNoDebugException()
    {
        $exception = $this->createException(new MockException("Exception message"));

        $this->eh->setDebug(false);

        $this->eh->addHandler(function ($message, $extra) use ($exception) {

            $this->assertArrayNotHasKey('memory', $extra);

            $exception->setHandled(true);
        });

        $this->handleException($exception);
        $this->assertTrue($exception->getHandled());
    }
}
