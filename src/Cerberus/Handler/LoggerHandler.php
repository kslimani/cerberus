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
    protected $exceptionLogLevel;

    public function __construct(LoggerInterface $logger)
    {
        $this->setPriority(100);
        $this->setHandleNonFatal(true);
        $this->logger = $logger;
        $this->errorLogLevels = $this->defaultErrorLogLevels();
        $this->exceptionLogLevel = LogLevel::CRITICAL;
    }

    public function handle($type, $displayType, $message, $file, $line, $extra)
    {
        $this->logger->log(
            isset($extra['exception']) ? $this->exceptionLogLevel($extra['exception']) : $this->errorLogLevel($type),
            sprintf('%s: %s in %s line %s', $displayType, $message, $file, $line),
            $extra
        );

        return false;
    }

    public function setErrorLogLevels($errorLogLevels = array())
    {
        $this->errorLogLevels = array_replace($this->defaultErrorLogLevels(), $errorLogLevels);
    }

    public function setExceptionLogLevel($exceptionLogLevel)
    {
        $this->exceptionLogLevel = $exceptionLogLevel;
    }

    private function defaultErrorLogLevels()
    {
        return array(
            E_ERROR             => LogLevel::CRITICAL,
            E_WARNING           => LogLevel::WARNING,
            E_PARSE             => LogLevel::ALERT,
            E_NOTICE            => LogLevel::NOTICE,
            E_CORE_ERROR        => LogLevel::CRITICAL,
            E_CORE_WARNING      => LogLevel::WARNING,
            E_COMPILE_ERROR     => LogLevel::ALERT,
            E_COMPILE_WARNING   => LogLevel::WARNING,
            E_USER_ERROR        => LogLevel::ERROR,
            E_USER_WARNING      => LogLevel::WARNING,
            E_USER_NOTICE       => LogLevel::NOTICE,
            E_STRICT            => LogLevel::NOTICE,
            E_RECOVERABLE_ERROR => LogLevel::ERROR,
            E_DEPRECATED        => LogLevel::NOTICE,
            E_USER_DEPRECATED   => LogLevel::NOTICE
        );
    }

    private function exceptionLogLevel($exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            if ($exception->getStatusCode() < 500) {
                return LogLevel::WARNING;
            }
        } elseif ($exception instanceof \ErrorException) {
            return $this->errorLogLevel($exception->getSeverity());
        }

        return $this->exceptionLogLevel;
    }

    private function errorLogLevel($type)
    {
        return isset($this->errorLogLevels[$type]) ? $this->errorLogLevels[$type] : LogLevel::CRITICAL;
    }
}
