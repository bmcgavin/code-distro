<?php

namespace Codedistro;

class Client extends Shared {
    
    public static $config = null;
    public static $log = null;

    protected $ctx = null;
    protected $subSock = null;
    protected $reqSock = null;

    public function __construct($config) {
        $this->readConfig($config);
        $this->setupLogging(self::$config['debug_log']);
        if (!$this->initZmq()) {
            die(1);
        }
        if (!$this->connectZmq(self::$config['connect_sub_port'], self::$config['connect_sub_type'])) {
            die(2);
        }
        $this->subLoop();
    }

    public function subLoop() {
        //Firehose
        $this->subSock->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, '');
        while (true) {
            $message = $this->subSock->recv();
            self::$log->addDebug('Got message : ' . print_r($message, true));
            $this->dispatch($message);
        }
    }
}
