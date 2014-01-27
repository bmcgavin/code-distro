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
    private $repSock = null;
    private $pubSock = null;

    public function __construct($config) {
        $this->readConfig($config);
        $this->setupLogging(self::$config['debug_log']);
        if (!$this->initZmq()) {
            die(1);
        }
        if (!$this->bindZmq(self::$config['bind_pub_port'], self::$config['bind_pub_type'])) {
            die(2);
        }
        if (!$this->bindZmq(self::$config['bind_rep_port'], self::$config['bind_rep_type'])) {
            die(3);
        }
        $this->repLoop();
    }

    public function publish($message) {
        try {
            $this->pubSock->send($message);
        } catch (Exception $e) {
            self::$log->addError('Could not publish : ' . $e->getMessage());
        }
        return false;
    }

    public function repLoop() {
        while (true) {
            $message = $this->repSock->recv();
            self::$log->addDebug('Got message :' . print_r($message));

            //need to ack
            $this->repSock->send('ack');
            self::$log->addDebug('Sent ack');
            self::$log->addDebug('Publishing...');
            $this->publish($message);
        }
    }

    private function bindZmq($port, $type) {
        try {
            switch($type) {
            case \ZMQ::SOCKET_PUB:
                $varName = 'pubSock';
                break;
            case \ZMQ::SOCKET_REP:
                $varName = 'repSock';
                break;
            default:
                throw new Exception('Unknown value ' . $type);
            }
            $this->$varName = new \ZMQSocket($this->ctx, $type);
            $this->$varName->bind("tcp://127.0.0.1:$port");
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

