<?php

namespace Codedistro;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Client extends Shared {
    
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
        if (!$this->bindZmq(self::$config['bind_sub_port'], self::$config['bind_sub_type'])) {
            die(2);
        }
        $this->subLoop();
    }

    public function subLoop() {
        while (true) {
            $message = $this->sock->recv();
            self::$log->addDebug('Got message : ' . print_r($message, true));
        }
    }
}