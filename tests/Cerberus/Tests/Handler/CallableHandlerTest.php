<?php

namespace Cerberus\Tests\Handler;

use Cerberus\ErrorHandler;
use Cerberus\Tests\Fixtures\MockError;
use Cerberus\Tests\Fixtures\MockException;
use Cerberus\Handler\CallableHandler;

class CallableHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $eh;

    protected function createError($displayType, $type, $message, $file, $line)
    {
        return new MockError($displayType, $type, $message, $file, $line);
    }

    protected function createException(MockException $exception)
    {
        try {
            throw $exception;
        } catch (\Exception $e) {
            return $e;
        }
        $this->fail('createException failed to throw exception');
    }

    protected function handleError(MockError $error)
    {
        $this->eh->onError(
            $error->getType(),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );
    }

    protected function handleException(MockException $exception)
    {
        $this->eh->onException($exception);
    }

    public function setUp()
    {
        $this->eh = new ErrorHandler();
    }

    public function tearDown()
    {
        $this->eh->unRegister();
        unset($this->eh);
    }

    public function testBadArgument()
    {
        $this->setExpectedException('InvalidArgumentException');
        $handler = new CallableHandler('BAD.ARGUMENT');
    }

    public function testIgnoreNonFatalErrors()
    {
        $this->eh->addHandler(function ($message, $extra) {
            $this->fail('Non fatal error handled');
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

            $this->assertArrayNotHasKey('displayType', $extra);
            $this->assertArrayNotHasKey('file', $extra);
            $this->assertArrayNotHasKey('line', $extra);
            $this->assertArrayNotHasKey('message', $extra);
            $this->assertArrayNotHasKey('type', $extra);
            $this->assertArrayHasKey('exception', $extra);

            $e = $extra['exception'];

            $this->assertInstanceOf('Exception', $e);
            $this->assertEquals($exception->getDisplayType(), get_class($e));
            $this->assertEquals($exception->getCode(), $e->getCode());
            $this->assertEquals($exception->getMessage(), $e->getMessage());
            $this->assertEquals($exception->getFile(), $e->getFile());
            $this->assertEquals($exception->getLine(), $e->getLine());

            $exception->setHandled(true);
        }, true);

        $this->handleException($exception);
        $this->assertTrue($exception->getHandled());
    }

}
