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

    public function __construct($fileName, $logName = 'log', $logLevel = \Monolog\Logger::DEBUG) {
        if (!defined($logLevel)) {
            throw new \Exception('Unknown log level');
        }
        $this->log = new MLogger($logName);
        $this->log->pushHandler(
            new StreamHandler($fileName, constant($logLevel))
        );
        $this->log->addDebug('Got logfile');
    }

    public function __call($name, $arguments) {
        if (is_object($this->log)) {
            $this->log->{$name}($arguments[0]);
        }
    }
}
