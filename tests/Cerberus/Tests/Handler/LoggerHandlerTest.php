<?php

namespace Cerberus\Tests\Handler;

use Cerberus\Tests\HandlerTestCase;
use Cerberus\Tests\Fixtures\MockException;
use Cerberus\Tests\Fixtures\MockHttpException;
use Cerberus\Tests\Fixtures\MockLogger;
use Cerberus\Handler\LoggerHandler;
use Psr\Log\LogLevel;

class LoggerHandlerTest extends HandlerTestCase
{
    protected $logger;
    protected $loggerHandler;

    public function setUp()
    {
        parent::SetUp();
        $this->logger = new MockLogger();
        $this->loggerHandler = new LoggerHandler($this->logger);
        $this->eh->addHandler($this->loggerHandler);
    }

    protected function assertLine($lineNumber)
    {
        $this->assertEquals($lineNumber, $this->logger->getLineCount());
        $line = $this->logger->getLine($lineNumber);
        $this->assertArrayHasKey('level', $line);
        $this->assertArrayHasKey('message', $line);
        $this->assertArrayHasKey('context', $line);

        return $line;
    }

    protected function formatedExceptionMessage(\Exception $exception)
    {
        return sprintf(
            '%s: %s in %s line %s',
            $exception->getDisplayType(),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->GetLine()
        );
    }

    public function testHandlerSetter()
    {
        $this->assertTrue($this->loggerHandler->getCallNextHandler());
        $this->loggerHandler->setCallNextHandler(false);
        $this->assertNotTrue($this->loggerHandler->getCallNextHandler());
    }

    public function testHandleError()
    {
        $error = $this->createError('E_NOTICE', E_NOTICE, 'Error Message', 'file.php', 5);
        $this->handleError($error);
        $expectedMessage = sprintf(
            '%s: %s in %s line %s',
            $error->getDisplayType(),
            $error->getMessage(),
            $error->getFile(),
            $error->GetLine()
        );

        $line = $this->assertLine(1);
        $this->assertEquals(LogLevel::NOTICE, $line['level']);
        $this->assertEquals($expectedMessage, $line['message']);

        // Test custom error log level feature
        $this->loggerHandler->setErrorLogLevels(array(E_NOTICE => LogLevel::INFO));
        $this->handleError($error);

        $line = $this->assertLine(2);
        $this->assertEquals(LogLevel::INFO, $line['level']);

        // Test error exception conversion
        $this->eh->setThrowExceptions(true);
        $this->eh->setThrowNonFatal(true);
        try {
            $this->handleError($error);
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        $line = $this->assertLine(3);
        $context = $line['context'];
        $this->assertEquals(LogLevel::INFO, $line['level']);
        $this->assertArrayHasKey('displayType', $context);

        $expectedMessage = sprintf(
            '%s: %s in %s line %s',
            $context['displayType'],
            $error->getMessage(),
            $error->getFile(),
            $error->GetLine()
        );

        $this->assertEquals($expectedMessage, $line['message']);
    }

    public function testHandleException()
    {
        $exception = $this->createException(new MockException("Exception message"));
        $this->handleException($exception);

        $line = $this->assertLine(1);
        $this->assertEquals(LogLevel::CRITICAL, $line['level']);
        $this->assertEquals($this->formatedExceptionMessage($exception), $line['message']);
    }

    public function testHandleHttpException()
    {
        // Non-critical HTTP exception
        $exception = $this->createException(new MockHttpException(404, "Not found message"));
        $this->handleException($exception);

        $line = $this->assertLine(1);
        $this->assertEquals(LogLevel::WARNING, $line['level']);
        $this->assertEquals($this->formatedExceptionMessage($exception), $line['message']);

        // Critical HTTP exception
        $exception = $this->createException(new MockHttpException(500, "Critical message"));
        $this->handleException($exception);

        $line = $this->assertLine(2);
        $this->assertEquals(LogLevel::CRITICAL, $line['level']);
        $this->assertEquals($this->formatedExceptionMessage($exception), $line['message']);

        // Non-critical HTTP exception with custom log level
        $exception = $this->createException(new MockHttpException(404, "Not found message"));
        $this->loggerHandler->setNonCriticalHttpExceptionLogLevel(LogLevel::NOTICE);
        $this->handleException($exception);

        $line = $this->assertLine(3);
        $this->assertEquals(LogLevel::NOTICE, $line['level']);
        $this->assertEquals($this->formatedExceptionMessage($exception), $line['message']);

        // Critical HTTP exception with custom log level
        $exception = $this->createException(new MockHttpException(500, "Critical message"));
        $this->loggerHandler->setCriticalHttpExceptionLogLevel(LogLevel::ALERT);
        $this->handleException($exception);

        $line = $this->assertLine(4);
        $this->assertEquals(LogLevel::ALERT, $line['level']);
        $this->assertEquals($this->formatedExceptionMessage($exception), $line['message']);
    }

    public function testHandleHttpExceptionFilterLevel()
    {
        // Default HttpExceptionInterface filter level value is 500
        $this->assertEquals(500, $this->loggerHandler->getHttpExceptionInterfaceFilterLevel());

        $this->loggerHandler->setHttpExceptionInterfaceFilterLevel(400);
        $exception = $this->createException(new MockHttpException(404, "Not found message"));
        $this->handleException($exception);

        $line = $this->assertLine(1);
        $this->assertEquals(LogLevel::CRITICAL, $line['level']);
        $this->assertEquals($this->formatedExceptionMessage($exception), $line['message']);
        $this->assertEquals(400, $this->loggerHandler->getHttpExceptionInterfaceFilterLevel());
    }

}
