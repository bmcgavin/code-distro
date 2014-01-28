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
            'publish'      => false,
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

    protected function connectZmq($port, $type) {
        try {
            switch($type) {
            case \ZMQ::SOCKET_SUB:
                $varName = 'subSock';
                break;
            case \ZMQ::SOCKET_REQ:
                $varName = 'reqSock';
                break;
            default:
                throw new \Exception('Unknown socket type ' . $type);
            }
            $this->$varName = new \ZMQSocket($this->ctx, $type);
            $this->$varName->connect("tcp://127.0.0.1:$port");
        } catch (\Exception $e) {
            static::$log->addError('Could not create socket or connect to port ' . $port . ': ' . $e->getMessage());
            return false;
        }
        static::$log->addDebug('Connected to port ' . $port);
        return $this->$varName;
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
                throw new \Exception('Unknown socket type ' . $type);
            }
            $this->$varName = new \ZMQSocket($this->ctx, $type);
            $this->$varName->bind("tcp://127.0.0.1:$port");
        } catch (\Exception $e) {
            static::$log->addError('Could not create socket or bind on port ' . $port . ': ' . $e->getMessage());
            return false;
        }
        static::$log->addDebug('Bound on port ' . $port);
        return true;
    }

    protected function dispatch($message) {
        $obj = json_decode($message);
        if ($obj === null) {
            static::$log->addError('Received message is not valid json');
            return false;
        }
        if (!property_exists($obj, 'type')) {
            static::$log->addError('object does not contain type property');
            return false;
        }
        if (!array_key_exists($obj->type . '_port', static::$config)) {
            static::$log->addError('do not know how to dispatch ' . $obj->type);
            return false;
        }
        $this->connectZmq(static::$config[$obj->type . '_port'], \ZMQ::SOCKET_REQ)->send($obj->payload);
        static::$log->addDebug('sent payload');
    }

    protected function validateArray($array, $message) {
        static::$log->addDebug('validateArray array input : ' . print_r($array, true));
        static::$log->addDebug('validateArray message input : ' . print_r($message, true));
        foreach($array as $key => $value) {
            if (!property_exists($message, $key)) {
                throw new \Exception('$message has no ' . $key);
            }
            if (is_array($value)) {
                $this->validateArray($value, $message->{$key});
            } else {
                $this->data[$key] = $message->{$key};
            }

        } 
    }


}
