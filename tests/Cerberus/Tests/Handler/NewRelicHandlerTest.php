<?php

namespace Cerberus\Tests\Handler;

use Cerberus\Tests\HandlerTestCase;
use Cerberus\Tests\Fixtures\MockException;
use Cerberus\Handler\NewRelicHandler;

class NewRelicHandlerTest extends HandlerTestCase
{
    public static $appName;
    public static $transactionName;
    public static $message;
    public static $exception;

    public function testNoExtension()
    {
        $this->setExpectedException('Exception', 'The newrelic PHP extension is required to use the NewRelicHandler');
        $handler = new NewRelicHandlerWithoutExtension();
    }

    public function setUp()
    {
        self::$appName = "";
        self::$transactionName = "";
        self::$message = "";
        self::$exception = null;
        parent::SetUp();
    }

    protected function formatErrorMessage($error)
    {
        return sprintf(
            '%s: %s in %s line %s',
            $error->getDisplayType(),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );
    }

    public function testError()
    {
        $handler = new NewRelicHandlerWithExtension();
        $this->eh->addHandler($handler);

        $error = $this->createError('E_ERROR', E_ERROR, 'Error Message', 'file.php', 5);
        $this->handleError($error);

        $this->assertEquals($this->formatErrorMessage($error), self::$message);
        $this->assertNull(self::$exception);
    }

    public function testException()
    {
        $handler = new NewRelicHandlerWithExtension();
        $this->eh->addHandler($handler);

        $exception = $this->createException(new MockException("Exception message"));
        $this->handleException($exception);

        $this->assertInstanceOf('\Exception', self::$exception);
        $this->assertEquals($exception->getMessage(), self::$exception->getMessage());
        $this->assertEquals($exception->getCode(), self::$exception->getCode());
        $this->assertEquals($exception->getFile(), self::$exception->getFile());
        $this->assertEquals($exception->getLine(), self::$exception->getLine());
    }

    public function testAppName()
    {
        $handler = new NewRelicHandlerWithExtension(false, "MockedApplicationName");

        $this->assertEquals("MockedApplicationName", self::$appName);

        $handler->setAppName("AnotherName");
        $this->assertEquals("AnotherName", self::$appName);
    }

    public function testTransactionName()
    {
        $handler = new NewRelicHandlerWithExtension();

        $this->assertEmpty(self::$transactionName);

        $handler->setTransactionName("MockedTransactionName");
        $this->assertEquals("MockedTransactionName", self::$transactionName);
    }

    public function testHttpExceptionInterfaceFilterLevel()
    {
        $handler = new NewRelicHandlerWithExtension();

        $this->assertEquals(500, $handler->getHttpExceptionInterfaceFilterLevel());

        $handler->setHttpExceptionInterfaceFilterLevel(404);
        $this->assertEquals(404, $handler->getHttpExceptionInterfaceFilterLevel());

        // TODO: test http exception filtering ?
    }

    public function testIgnoreNonFatalErrors()
    {
        $handler = new NewRelicHandlerWithExtension();
        $this->eh->addHandler($handler);

        $error = $this->createError('E_NOTICE', E_NOTICE, 'Error Message', 'file.php', 5);
        $this->handleError($error);

        $this->assertEquals('', self::$message);
        $this->assertNull(self::$exception);
    }

    public function testHandleNonFatalErrors()
    {
        $handler = new NewRelicHandlerWithExtension(true);
        $this->eh->addHandler($handler);

        $error = $this->createError('E_NOTICE', E_NOTICE, 'Error Message', 'file.php', 5);
        $this->handleError($error);

        $this->assertEquals($this->formatErrorMessage($error), self::$message);
        $this->assertNull(self::$exception);
    }
}

class NewRelicHandlerWithoutExtension extends NewRelicHandler
{
    public function isNewRelicExtensionLoaded()
    {
        return false;
    }
}

class NewRelicHandlerWithExtension extends NewRelicHandler
{
    public function isNewRelicExtensionLoaded()
    {
        return true;
    }
}

// Mock newrelic extension functions
namespace Cerberus\Handler;

function newrelic_notice_error($message, $exception = null)
{
    \Cerberus\Tests\Handler\NewRelicHandlerTest::$message = $message;
    \Cerberus\Tests\Handler\NewRelicHandlerTest::$exception = $exception;

    return true;
}

function newrelic_set_appname($name)
{
    \Cerberus\Tests\Handler\NewRelicHandlerTest::$appName = $name;
}

function newrelic_name_transaction($name)
{
    \Cerberus\Tests\Handler\NewRelicHandlerTest::$transactionName = $name;
}
