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
        try {
            $this->logger = new Logger($this->config->logFile, 'Server', $this->config->logLevel);
        } catch (\Exception $e) {
            $error = __CLASS__ . ": Could not initiate logger : " . $e->getMessage();
            error_log($error);
            die(1);

        }
        $className = "Codedistro\Broker\\" . $this->config->brokerType . 'Broker';
        $this->broker = new $className();
        if (!$this->broker->init($this->logger)) {
            die(1);
        }
        try {
            //Bind as a server
            $config = $this->config->serverIncoming[$this->config->brokerType];
            $input = $this->broker->connect($config);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die(2);
        }
        try {
            //Bind as another server
            $config = $this->config->serverOutgoing[$this->config->brokerType];
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
            $m = Message::getInstance($this->logger, $message);
            //need to ack
            $this->logger->addDebug('Acking');
            $e = new Encryption($this->config->keyLocation);
            $this->broker->send($input, new Message($this->logger, '"ack"', 'ack', $e));
            unset($e);

            $this->logger->addDebug('Publishing...:' . $m);
            $this->broker->send($output, $m);
            $this->logger->addDebug('Published');
            
        }
    }
}

