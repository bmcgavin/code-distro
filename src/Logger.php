<?php

namespace Codedistro;

use Monolog\Logger as MLogger;
use Monolog\Handler\StreamHandler;

/**
 * Logger class should be static, really.
 * No it shouldn't! Dependency Injection
 */
class Logger {

    private $log = null;

    public function __construct($filename, $logname = 'log', $loglevel = MLogger::DEBUG) {
        $this->log = new MLogger($logname);
        $this->log->pushHandler(
            new StreamHandler($filename, $loglevel)
        );
        $this->log->addDebug('Got logfile');
    }

    public function __call($name, $arguments) {
        if (is_object($this->log)) {
            $this->log->{$name}($arguments[0]);
        }
    }
}
