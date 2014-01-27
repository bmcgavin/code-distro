<?php

/**
 * Codedistro - distribute git repository patches over 0mq
 */

namespace Codedistro;

/**
 * Server - receive and ack the Github hook POSTs, and then publish
 * over a 0mq PUB link
 */

class Server extends Shared {

    public static $config = null;
    public static $log = null;

    protected $ctx = null;
    protected $repSock = null;
    protected $pubSock = null;

    public function __construct($config) {
        $this->readConfig($config);
        $this->setupLogging(self::$config['debug_log']);
        if (!$this->initZmq()) {
            die(1);
        }
        if (self::$config['publish'] === true) {
            if (!$this->bindZmq(self::$config['bind_pub_port'], self::$config['bind_pub_type'])) {
                die(2);
            }
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
            self::$log->addDebug('Got message :' . print_r($message, true));

            //need to ack
            $this->repSock->send('ack');
            self::$log->addDebug('Sent ack');
            if (self::$config['publish'] == true) {
                self::$log->addDebug('Publishing...');
                $this->publish($message);
            }
            
            if (
                array_key_exists('process', self::$config)
             && class_exists(__NAMESPACE__ . '\\' . self::$config['process'])
            ) {
                $className = __NAMESPACE__ . '\\' . self::$config['process'];
                self::$log->addDebug('Processing with ' . $className);
                $c = new $className(self::$log);
                $c->process($message);
            }
        }
    }



}

