<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus;

use Cerberus\Handler\Handler;
use Cerberus\Handler\HandlerList;
use Cerberus\Handler\CallableHandler;
use Cerberus\Exception\ContextErrorException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ErrorHandler
{
    const E_EXCEPTION = 0;

    private $reservedMemory;
    protected $disabled;
    protected $handlerList;
    protected $debug;
    protected $throwExceptions;
    protected $throwNonFatal;
    protected $previousErrorHandler;
    protected $previousExceptionHandler;
    protected $registered;

    public function __construct($debug = true, $throwExceptions = false, $throwNonFatal = false)
    {
        ini_set('display_errors', 0);
        $this->reservedMemory = str_repeat('0', 20480);
        $this->handlerList = new HandlerList($this);
        $this->enable();
        $this->setDebug($debug);
        $this->setThrowExceptions($throwExceptions);
        $this->setThrowNonFatal($throwNonFatal);
        $this->register();
    }

    protected function register()
    {
        $this->previousErrorHandler = set_error_handler(array($this, 'onError'));
        $this->previousExceptionHandler = set_exception_handler(array($this, 'onException'));
        register_shutdown_function(array($this, 'onShutdown'));
        $this->registered = true;
    }

    public function unRegister()
    {
        set_error_handler($this->previousErrorHandler);
        set_exception_handler($this->previousExceptionHandler);
        $this->registered = false;
    }

    public function enable()
    {
        $this->disabled = false;
    }

    public function disable()
    {
        $this->disabled = true;
    }

    public function setDebug($debug)
    {
        $this->debug = $debug;

        return $this;
    }

    public function getDebug()
    {
        return $this->debug;
    }

    public function setThrowExceptions($throwExceptions)
    {
        $this->throwExceptions = $throwExceptions;

        return $this;
    }

    public function getThrowExceptions()
    {
        return $this->throwExceptions;
    }

    public function setThrowNonFatal($throwNonFatal)
    {
        $this->throwNonFatal = $throwNonFatal;

        return $this;
    }

    public function getThrowNonFatal()
    {
        return $this->throwNonFatal;
    }

    public function addHandler($handler, $handleNonFatal = false)
    {
        if (is_callable($handler)) {
            $handler = new CallableHandler($handler, $handleNonFatal);
        }

        $this->handlerList->addHandler($handler);

        return $this;
    }

    public function onError($type, $message, $file = '', $line = 0, $context = array())
    {
        if (($this->throwExceptions) && ($this->throwNonFatal || $this->isFatal($type))) {
            if ($context) {
                throw new ContextErrorException($message, 0, $type, $file, $line, $context);
            } else {
                throw new \ErrorException($message, 0, $type, $file, $line);
            }
        }

        return $this->handle(
            $type,
            $message,
            $file,
            $line,
            $this->getErrorExtra(array(
                'displayType' => self::errorType($type),
                'context' => $context
            ))
        );
    }

    public function onException(\Exception $e)
    {
        if ($e instanceof \ErrorException) {
            $displayType = sprintf("%s (%s)", get_class($e), self::errorType($e->getSeverity()));
        } else {
            $displayType = get_class($e);
        }

        return $this->handle(
            self::E_EXCEPTION,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $this->getErrorExtra(array(
                'displayType' => $displayType,
                'exception' => $e
            ))
        );
    }

    public function onShutdown()
    {
        // Shutdown callbacks can't be unregistered
        if (!$this->registered) {
            return;
        }
        $this->reservedMemory = '';
        gc_collect_cycles();
        $err = error_get_last();

        if ($err) {
            return $this->handle(
                $err['type'],
                $err['message'],
                $err['file'],
                $err['line'],
                $this->getErrorExtra(array(
                    'displayType' => self::errorType($err['type'])
                ))
            );
        }
    }

    protected function getErrorExtra($extra = array())
    {
        if ($this->getDebug()) {
            $extra['memory'] = memory_get_peak_usage(false);
            if (!isset($extra['exception'])) {
                $extra['trace'] = debug_backtrace(false);
            }
        }
        if (isset($extra['exception']) && $extra['exception'] instanceof HttpExceptionInterface) {
            $extra['code'] = $extra['exception']->getStatusCode();
        }

        return $extra;
    }

    protected function handle($type, $displayType, $message, $file, $line, $extra = array())
    {
        if ($this->disabled) {
            return false;
        }

        foreach ($this->handlerList as $handler) {
            if (true === $handler->handle($type, $displayType, $message, $file, $line, $extra)) {
                return true;
            }
        }

        // TODO : restore previous handler and return false ?
        return true;
    }

    public function isFatal($type)
    {
        return in_array(
            $type,
            array(
                self::E_EXCEPTION,
                E_ERROR,
                E_PARSE,
                E_CORE_ERROR,
                E_COMPILE_ERROR,
                E_USER_ERROR
            )
        );
    }

    protected static function errorType($type)
    {
        switch ($type) {
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
        }

        return 'E_UNKNOWN';
    }

    public function emptyOutputBuffers()
    {
        $ob = '';
        while (ob_get_level()) {
            $ob .= ob_get_clean();
        }

        return $ob;
    }
}
