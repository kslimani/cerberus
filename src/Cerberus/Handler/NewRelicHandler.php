<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

class NewRelicHandler extends Handler
{
    public function __construct($handleNonFatal = false, $appName = null)
    {
        if (!$this->isNewRelicExtensionLoaded()) {
            throw new \Exception('The newrelic PHP extension is required to use the NewRelicHandler');
        }

        $this->setHandleNonFatal($handleNonFatal);

        if (!is_null($appName)) {
            $this->setAppName($appName);
        }
    }

    public function handle($type, $message, $file, $line, $extra)
    {
        if ($this->canIgnoreError($type)) {
            return;
        }

        if (isset($extra['exception'])) {
            newrelic_notice_error($message, $extra['exception']);
        } else {
            newrelic_notice_error($message);
        }

        return false;
    }

    public function setAppName($name)
    {
        newrelic_set_appname($name);
    }

    public function setTransactionName($name)
    {
        newrelic_name_transaction($name);
    }

    public function isNewRelicExtensionLoaded()
    {
        return extension_loaded('newrelic');
    }
}
