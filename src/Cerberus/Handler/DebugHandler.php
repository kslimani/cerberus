<?php

/*
 * This file is part of the Cerberus package.
 */

namespace Cerberus\Handler;

class DebugHandler extends Handler
{
    protected $version = '0.1.0 beta';
    protected $charset = 'utf-8';
    protected $maxArgDisplaySize = 4096;
    protected $maxErrorCount = 30;

    public function __construct($handleNonFatal = false)
    {
        $this->setPriority(0);
        $this->setHandleNonFatal($handleNonFatal);
    }

    public function handle($type, $displayType, $message, $file, $line, $extra)
    {
        if ($this->canIgnoreError($type)) {
            return;
        }

        $handler = $this->getErrorHandler();
        $template = realpath(dirname(__FILE__).'/../Resources/DebugHandler/index.template');

        if (!$handler->getDebug() || !is_file($template)) {
            return $this->displayImpersonalErrorPage();
        }

        // TODO: rewrite legacy html formatters from scratch & add mode debug informations
        $content = sprintf(
            "<li><h2>%s</h2><ul><li>Memory used : %s</li>%s</ul></li>".
            "<li><h2>Output Buffer</h2><ul><li><code>%s</code></li></ul></li>",
            sprintf("%s: %s in %s line %s", $displayType, $message, $file, $line),
            $this->formatMemory($this->getMemory($extra)),
            $this->traceToHtml($this->getTrace($extra)),
            htmlentities($handler->emptyOutputBuffers(), ENT_COMPAT, $this->charset)
        );

        return $this->sendResponse(
            $this->renderTemplate($template, array(
                'charset' => $this->charset,
                'version' => $this->version,
                'content' => $content
            ))
        );
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function setMaxArgDisplaySize($maxArgDisplaySize)
    {
        $this->maxArgDisplaySize = $maxArgDisplaySize;
    }

    public function setMaxErrorCount($maxErrorCount)
    {
        $this->maxErrorCount = $maxErrorCount;
    }

    private function renderTemplate($file, $values = array())
    {
        $template = file_get_contents($file);
        if (false === $template) {
            return "";
        }
        foreach ($values as $key => $value) {
            $template = preg_replace('/%'.$key.'%/', $value, $template, 1);
        }

        return $template;
    }

    private function formatMemory($memory)
    {
        $units = array('bytes','kilobytes','megabytes','gigabytes','terabytes','petabytes');
        $unit = floor(log($memory, 1024));

        return round($memory / pow(1024, $unit), 2).' '.$units[$unit];
    }

    private function traceToHtml($trace)
    {
        $html = "";
        $count = count($trace);
        for ($i = 0 ; $i < $count ; $i++) {
            $stack = &$trace[$i];
            if (isset($stack['file']) && ($stack['file'] === __FILE__)) {
                continue;
            }
            $msg = "";
            if (isset($stack['class'])) {
                $msg .= $stack['class'];
            }
            if (isset($stack['type'])) {
                $msg .= $stack['type'];
            }
            if (isset($stack['function'])) {
                $msg .= "{$stack['function']}()";
            }
            if (0 === strpos($msg, 'Cerberus\\')) {
                continue;
            }
            if (isset($stack['file'])) {
                $msg .= " in {$stack['file']}";
            }
            if (isset($stack['line'])) {
                $msg .= " line {$stack['line']}";
            }
            if (empty($msg) === true) {
                continue;
            }
            if (isset($stack['args']) && is_array($stack['args']) && (count($stack['args']) > 0)) {
                $args = $this->argsToHtml($stack['args']);
                $html .= "<li><h3>at $msg</h3><ul><li>$args</li></ul></li>\n";
                continue;
            }
            $html .= "<li>at $msg</li>\n";
        }
        if (empty($html) === true) {
            return "<li>Backtrace not available</li>\n";
        }

        return $html;
    }

    private function argsToHtml($args)
    {
        $html = "<table>";
        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            $arg = &$args[$i];
            $type  = gettype($arg);
            if ($type === 'boolean') {
                $text = ($arg) ? 'true' : 'false';
                $html .= "<tr><td><strong>$type</strong></td><td>$text</td></tr>";
                continue;
            }
            if (($type === 'array') || ($type === 'object')) {
                $text = $this->printReadable($arg, true);
                if (mb_strlen($text) > $this->maxArgDisplaySize) {
                    $text = ($type === 'object') ? get_class($arg)." object" : count($arg)." element(s)";
                    $text .= " <i>(content unavailable)</i>";
                }
                $html .= "<tr><td><strong>$type</strong></td><td><code>$text</code></td></tr>";
                continue;
            }
            if ($type === 'resource') {
                $type = get_resource_type($arg);
                $html .= "<tr><td>resource</td><td>$type</td></tr>";
                continue;
            }
            if (mb_strlen($arg) > $this->maxArgDisplaySize) {
                $html .= "<tr><td><strong>$type</strong></td><td><i>(content unavailable)</i></td></tr>";
            } else {
                $html .= "<tr><td><strong>$type</strong></td><td><code>$arg</code></td></tr>";
            }
        }

        return "$html</table>";
    }

    private function printReadable($array, $return = false, $depth = 0)
    {
        $items = array();
        $html = "";
        foreach ($array as $key => $value) {
            $type = gettype($value);
            if ($type === "array") {
                if (count($value) > 0) {
                    $value = $this->printReadable($value, $return, $depth + 1);
                } else {
                    $value = ucfirst($type)." (empty)";
                }
            } else {
                switch ($type) {
                    case "NULL":
                        $value = $type;
                        break;
                    case "boolean":
                        $value = ($value === true) ? "true" : "false";
                        break;
                    case "string":
                        $value = "'{$value}'";
                        break;
                    case "object":
                        $value = get_class($value)." object";
                    default:
                        $value = $value;
                }
            }
            $items[$key] = htmlentities($value);
        }
        if (count($items) > 0) {
            $prefix = $tabs = "";
            for ($i = 0; $i < $depth; $i++) {
                $tabs .= "   ";
            }
            $array = (gettype($array) === 'object') ? get_class($array) : gettype($array);
            $html .= ucfirst($array)."\n{$tabs}(\n";
            foreach ($items as $key => &$value) {
                $html .= "{$prefix}{$tabs}   [".( is_string($key) === true ? "'{$key}'" : $key )."] => {$value}";
                $prefix = ",\n";
            }
            $html .= "\n{$tabs})";
        } else {
            if ('object' === gettype($array)) {
                return get_class($array)." ()";
            } else {
                return "Array ()";
            }
        }
        if ($return === true) {
            return $html;
        }
        echo $html;
    }

    private function displayImpersonalErrorPage()
    {
        $this->getErrorHandler()->emptyOutputBuffers();
        $content = "<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">\n";
        $content .= "<html><head>\n";
        $content .= "<title></title>\n";
        $content .= "</head><body>\n";
        $content .= "<h1>Internal Server Error</h1>\n";
        $content .= "<p>The server encountered an internal error and was unable to complete your request.</p>\n";
        $content .= "</body></html>";

        return $this->sendResponse($content);
    }

    private function sendErrorHeader()
    {
        if (headers_sent() || !isset($_SERVER["REQUEST_URI"])) {
            return;
        }
        if (strpos(PHP_SAPI, 'cgi') > 0) {
            header('Status: 500 Internal Server Error');
        } else {
            header('HTTP/1.1 500 Internal Server Error');
        }
    }

    private function sendResponse($content)
    {
        $this->sendErrorHeader();
        echo $content;
        exit(1);

        return true;
    }

}
