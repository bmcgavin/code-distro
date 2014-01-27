<?php

namespace Codedistro;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

abstract class Shared {

    protected function initZmq() {
        try {
            $this->ctx = new \ZMQContext();
        } catch (\Exception $e) {
            static::$log->addError('Could not start ZMQ context : ' . $e->getMessage());
            return false;
        }
        static::$log->addDebug('Got 0mq ctx');
        return true;
    }

    protected function readConfig($configFile) {
        $defaults = array(
            'debug_log' => '/var/log/code_distro/server_debug.log',
            'bind_port'    => 5555,
        );
        try {
            //Read config
            if (!file_exists($configFile)) {
                throw new \Exception('Cannot find file ' . $configFile);
            }
            include_once($configFile);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            die($e->getMessage());
        }
        static::$config = array_merge($defaults, $config);
        return true;
    }

    protected function setupLogging($filename, $logname = 'debug', $loglevel = Logger::DEBUG) {
        static::$log = new Logger($logname);
        static::$log->pushHandler(
            new StreamHandler($filename, $loglevel)
        );
        static::$log->addDebug('Got logfile');
        return true;
    }

    protected function bindZmq($port, $type) {
        try {
            switch($type) {
            case \ZMQ::SOCKET_PUB:
                $varName = 'pubSock';
                break;
            case \ZMQ::SOCKET_REP:
                $varName = 'repSock';
                break;
            default:
                $varName = 'sock';
            }
            $this->$varName = new \ZMQSocket($this->ctx, $type);
            $this->$varName->bind("tcp://127.0.0.1:$port");
        } catch (\Exception $e) {
            static::$log->addError('Could not create queue or bind on port ' . $port . ': ' . $e->getMessage());
            return false;
        }
        static::$log->addDebug('Bound on port ' . $port);
        return true;
    }
}
