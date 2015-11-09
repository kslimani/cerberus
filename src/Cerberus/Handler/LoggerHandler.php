<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LoggerHandler extends Handler
{
    protected $logger;
    protected $errorLogLevels;
    protected $criticalHttpExceptionLogLevel = LogLevel::CRITICAL;
    protected $nonCriticalHttpExceptionLogLevel = LogLevel::WARNING;
    protected $httpExceptionCodeLevel = 500;

    public function __construct(LoggerInterface $logger, $priority = 100, $handleNonFatal = true, $callNextHandler = true)
    {
        $this->logger = $logger;
        $this->errorLogLevels = $this->defaultErrorLogLevels();
        $this->setPriority($priority);
        $this->setHandleNonFatal($handleNonFatal);
        if (!$callNextHandler) {
            $this->setCallNextHandler(false);
        }
    }

    public function handle($type, $message, $file, $line, $extra)
    {
        if (!isset($extra['exception'])) {
            $extra += array(
                'file' => $file,
                'line' => $line,
                'message' => $message,
                'type' => $type,
            );
        }

        $this->logger->log(
            isset($extra['exception']) ? $this->exceptionLogLevel($extra['exception']) : $this->errorLogLevel($type),
            sprintf('%s: %s in %s line %s', $this->getDisplayName($extra), $message, $file, $line),
            $extra
        );

        return (!$this->getCallNextHandler());
    }

    public function setHttpExceptionInterfaceFilterLevel($statusCode)
    {
        $this->httpExceptionCodeLevel = (int) $statusCode;
    }

    public function getHttpExceptionInterfaceFilterLevel()
    {
        return $this->httpExceptionCodeLevel;
    }

    public function setErrorLogLevels($errorLogLevels = array())
    {
        $this->errorLogLevels = array_replace($this->defaultErrorLogLevels(), $errorLogLevels);
    }

    public function setCriticalHttpExceptionLogLevel($criticalHttpExceptionLogLevel)
    {
        $this->criticalHttpExceptionLogLevel = $criticalHttpExceptionLogLevel;
    }

    public function setNonCriticalHttpExceptionLogLevel($nonCriticalHttpExceptionLogLevel)
    {
        $this->nonCriticalHttpExceptionLogLevel = $nonCriticalHttpExceptionLogLevel;
    }

    private function defaultErrorLogLevels()
    {
        return array(
            E_ERROR => LogLevel::CRITICAL,
            E_WARNING => LogLevel::WARNING,
            E_PARSE => LogLevel::ALERT,
            E_NOTICE => LogLevel::NOTICE,
            E_CORE_ERROR => LogLevel::CRITICAL,
            E_CORE_WARNING => LogLevel::WARNING,
            E_COMPILE_ERROR => LogLevel::ALERT,
            E_COMPILE_WARNING => LogLevel::WARNING,
            E_USER_ERROR => LogLevel::ERROR,
            E_USER_WARNING => LogLevel::WARNING,
            E_USER_NOTICE => LogLevel::NOTICE,
            E_STRICT => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED => LogLevel::NOTICE,
            E_USER_DEPRECATED => LogLevel::NOTICE,
        );
    }

    private function exceptionLogLevel($exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            // Symfony HttpExceptionInterface status code filtering
            if ($exception->getStatusCode() < $this->httpExceptionCodeLevel) {
                return $this->nonCriticalHttpExceptionLogLevel;
            }
        } elseif ($exception instanceof \ErrorException) {
            return $this->errorLogLevel($exception->getSeverity());
        }

        return $this->criticalHttpExceptionLogLevel;
    }

    private function errorLogLevel($type)
    {
        return isset($this->errorLogLevels[$type]) ? $this->errorLogLevels[$type] : LogLevel::CRITICAL;
    }
}
