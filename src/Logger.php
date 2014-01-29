<?php

namespace Codedistro;

use Monolog\Logger as MLogger;
use Monolog\Handler\StreamHandler;

class Logger {

    private static $log = null;

    public function __construct($filename, $logname = 'log', $loglevel = MLogger::DEBUG) {
        static::$log = new MLogger($logname);
        static::$log->pushHandler(
            new StreamHandler($filename, $loglevel)
        );
        static::$log->addDebug('Got logfile');
    }

    public function __call($name, $arguments) {
        if (is_object(static::$log)) {
            static::$log->{$name}($arguments[0]);
        }
    }
}
