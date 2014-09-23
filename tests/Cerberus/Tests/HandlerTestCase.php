<?php

namespace Cerberus\Tests;

use Cerberus\ErrorHandler;
use Cerberus\Tests\Fixtures\MockError;
use Cerberus\Tests\Fixtures\MockException;

abstract class HandlerTestCase extends \PHPUnit_Framework_TestCase
{
    protected $eh;

    protected function createContext()
    {
        return array(
            'mock' => 'context'
        );
    }

    protected function createError($displayType, $type, $message, $file, $line, $context = array())
    {
        return new MockError($displayType, $type, $message, $file, $line, $context);
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
            $error->getLine(),
            $error->getContext()
        );
    }

    protected function handleException(\Exception $exception)
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
}
