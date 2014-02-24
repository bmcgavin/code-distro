<?php

/**
 * Codedistro - distribute git repository patches over 0mq
 */

namespace Codedistro;

use Codedistro\Broker\ZeroMQBroker;


/**
 * Server - receive and ack the Github hook POSTs, and then publish
 * over a 0mq PUB link
 */

class Server {

    public $config = null;
    public $logger = null;
    public $broker = null;

    public function __construct($config) {
        $this->config = new Config($config);
        $this->logger = new Logger($this->config->debug_log, 'Server');
        $className = "Codedistro\Broker\\" . $this->config->broker_type . 'Broker';
        $this->broker = new $className();
        if (!$this->broker->init($this->logger)) {
            die(1);
        }
        try {
            //Bind as a server
            $config = $this->config->server_incoming[$this->config->broker_type];
            $input = $this->broker->connect($config);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die(2);
        }
        try {
            //Bind as another server
            $config = $this->config->server_outgoing[$this->config->broker_type];
            $output = $this->broker->connect($config);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die(3);
        }
        $this->repLoop($input, $output);
    }

    public function repLoop($input, $output) {
        while (true) {
            $message = $this->broker->recv($input);

            $this->logger->addDebug('Got message :' . print_r($message, true));
            //need to ack
            $this->logger->addDebug('Acking');
            $this->broker->send($input, 'ack');

            $this->logger->addDebug('Publishing...:' . $message);
            $this->broker->send($output, $message);
            $this->logger->addDebug('Published');
            
        }
    }
}

