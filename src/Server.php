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
    private $pubSock = null;
    private $recSock = null;

    public function __construct($config) {
        $this->readConfig($config);
        $this->setupLogging(self::$config['server_debug_log']);
        if (!$this->initZmq()) {
            die(1);
        }
        if (!$this->bindZmq(self::$config['bind_pub_port'], ZMQ::SOCKET_PUB)) {
            die(2);
        }
        if (!$this->bindZmq(self::$config['bind_rec_port'], ZMQ::SOCKET_REC)) {
            die(3);
        }
    }

    private function bindZmq($port, $type) {
        try {
            $queue = new \ZMQSocket($this->ctx, $type);
            $queue->bind("tcp://127.0.0.1:$port");
        } catch (Exception $e) {
            self::$log->addError('Could not create queue or bind on port ' . $port . ': ' . $e->getMessage());
            return false;
        }
    }

    private function initZmq() {
        try {
            $this->$ctx = new \ZMQContext();
        } catch (\Exception $e) {
            self::$log->addError('Could not start ZMQ context : ' . $e->getMessage());
            return false;
        }
    }

    private function readConfig($config) {
        $defaults = array(
            'server_debug_log' => '/var/log/code_distro/server_debug.log',
            'bind_pub_port'    => 5555,
            'bind_rec_port'    => 5556,
        );
        try {
            //Read config
            if (!file_exists($config)) {
                throw new Exception('Cannot find file ' . $config);
            }
            include_once($config);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            die($e->getMessage());
        }
        self::$config = array_merge($config, $defaults);
    }

    private function setupLogging($filename, $logname = 'debug', $loglevel = Logger::DEBUG) {
        self::$log = new Logger($logname);
        self::$log->pushHandler(
            new StreamHandler($filename, $loglevel)
        );
    }


}
