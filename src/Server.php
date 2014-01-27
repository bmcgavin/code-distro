<?php

/**
 * Codedistro - distribute git repository patches over 0mq
 */

namespace Codedistro;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Server - receive and ack the Github hook POSTs, and then publish
 * over a 0mq PUB link
 */

class Server {

    public static $config = null;
    public static $log = null;

    private $ctx = null;
    private $sock = null;

    public function __construct($config) {
        $this->readConfig($config);
        $this->setupLogging(self::$config['debug_log']);
        if (!$this->initZmq()) {
            die(1);
        }
        if (!$this->bindZmq(self::$config['bind_port'], self::$config['bind_type'])) {
            die(2);
        }
        while (true) {
            $message = $this->sock->recv();
        }
    }

    private function bindZmq($port, $type) {
        try {
            $this->sock = new \ZMQSocket($this->ctx, $type);
            $this->sock->bind("tcp://127.0.0.1:$port");
        } catch (Exception $e) {
            self::$log->addError('Could not create queue or bind on port ' . $port . ': ' . $e->getMessage());
            return false;
        }
        self::$log->addDebug('Bound on port ' . $port);
        return true;
    }

    private function initZmq() {
        try {
            $this->ctx = new \ZMQContext();
        } catch (\Exception $e) {
            self::$log->addError('Could not start ZMQ context : ' . $e->getMessage());
            return false;
        }
        self::$log->addDebug('Got 0mq ctx');
        return true;
    }

    private function readConfig($configFile) {
        $defaults = array(
            'debug_log' => '/var/log/code_distro/server_debug.log',
            'bind_port'    => 5555,
        );
        try {
            //Read config
            if (!file_exists($configFile)) {
                throw new Exception('Cannot find file ' . $configFile);
            }
            include_once($configFile);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            die($e->getMessage());
        }
        self::$config = array_merge($defaults, $config);
        return true;
    }

    private function setupLogging($filename, $logname = 'debug', $loglevel = Logger::DEBUG) {
        self::$log = new Logger($logname);
        self::$log->pushHandler(
            new StreamHandler($filename, $loglevel)
        );
        self::$log->addDebug('Got logfile');
        return true;
    }


}

