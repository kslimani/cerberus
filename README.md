# Cerberus error handler

Simple PHP error handler.

## Registering

Add in `composer.json` file :

```json
{
    "require": {
        "kslimani/cerberus": "~0.1.0"
    },
    "repositories": [
      {
        "type": "vcs",
        "url": "https://github.com/kslimani/cerberus.git"
      }
    ]
}
```

## Usage

Quick setup :

```php

error_reporting(-1);

use Cerberus\ErrorHandler;
use Cerberus\Handler\DebugHandler;

$errorHandler = new ErrorHandler;
$errorHandler->addHandler(new DebugHandler);

```

Cerberus come with a PSR-3 Logger Interface error handler :

```php

use Cerberus\ErrorHandler;
use Cerberus\Handler\LoggerHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$errorHandler = new ErrorHandler;
$logger = new Logger('errors');
$logger->pushHandler(new StreamHandler('/path/to/errors.log', Logger::NOTICE));
$errorHandler->addHandler(new LoggerHandler($logger));

```

Callable can also be added as handler :

```php

use Cerberus\ErrorHandler;

$errorHandler = new ErrorHandler;
$errorHandler->addHandler(function($message, $extra) {

    // $message is a formatted error message
    // $extra is an array with error/exception details

});

```

## Error/exception details

Details array content differ according to error type.

Error :
```json

[
    'displayType' : 'The error display type',
    'context'     : 'The application context',
    'memory'      : 'The memory peak usage, ONLY if debug is true',
    'trace'       : 'The error backtrace, ONLY if debug is true',
    'type'        : 'The error type, ONLY in CallableHandler',
    'message'     : 'The error message, ONLY in CallableHandler',
    'file'        : 'The error file, ONLY in CallableHandler',
    'line'        : 'The error line, ONLY in CallableHandler',
]

```

Exception :
```json

[
    'displayType' : 'The error display type',
    'exception'   : 'The exception object',
    'memory'      : 'The memory peak usage, ONLY if debug is true',
    'code'        : 'The http status code, ONLY if instance of HttpExceptionInterface',
]

```

Note : `HttpExceptionInterface` refer to `Symfony\Component\HttpKernel\Exception\HttpExceptionInterface`.

## Error handler priority

Error handlers are ordered by priority (from higher to lower values).

The first added handler get a priority value of 10, the second is 11, etc ...

Priority can be changed with `setPriority()` method and must be set BEFORE adding handler.

* `DebugHandler` default priority value is `0` (last handler)
* `LoggerHandler` default priority value is `100` (first handler)

## Silex integration example

This Silex example application should handle all PHP errors :

```php

use Cerberus\ErrorHandler;
use Cerberus\Handler\DebugHandler;
use Cerberus\Handler\LoggerHandler;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Response;

// Setup autoloader

require_once '/path/to/vendor/autoload.php';

$debug = true;
error_reporting(-1);
ini_set('display_errors', 0);

// Setup error handler

$errorHandler = new ErrorHandler;
$errorHandler
    ->setDebug($debug)
    ->setThrowExceptions(false)
    ->setThrowNonFatal(false)
;
if ($debug) {
    $errorHandler->addHandler(new DebugHandler(false));
}

$logger = new Logger('errors');
$logger->pushHandler(new StreamHandler('/path/to/errors.log', Logger::NOTICE));
$errorHandler->addHandler(new LoggerHandler($logger));

// Create and setup application

$app = new Application();

$app['debug'] = $debug;
$app['exception_handler']->disable();
$app['cerberus'] = $errorHandler;

// Register services

$app->register(new TwigServiceProvider());

// Register simple error pages service

$app['error.response'] = $app->protect(function ($code) use ($app) {
    if ($app->offsetExists('twig')) {

        // 404.html, or 40x.html, or 4xx.html, or default.html
        $templates = array(
            'errors/'.$code.'.html',
            'errors/'.substr($code, 0, 2).'x.html',
            'errors/'.substr($code, 0, 1).'xx.html',
            'errors/default.html'
        );

        return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
    } else {
        return new Response(sprintf("<h1>HTTP Error %s</h1>", $code), $code);
    }
});

// Register fatal error handler

$app['cerberus']->addHandler(function($message, $extra) use ($app) {

    if ($app['debug']) {
        return;
    }

    $app['cerberus']->emptyOutputBuffers();
    $response = $app['error.response'](500);

    if ($response instanceof Response) {
        $response->send();
    }

    return true;
});

// Register application exception handler

$app->error(function (\Exception $e, $code) use ($app) {

    if (($code !== 404) && $app['debug']) {
        return;
    }

    return $app['error.response']($code);
});

// Setup routes

$app->get('/hello/{name}', function($name) use($app) {
    return 'Hello ' . $app->escape($name);
});

// Run application

$app->run();

```
