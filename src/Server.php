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
        $this->logger = new Logger($this->config->debug_log);
        $className = "Codedistro\Broker\\" . $this->config->broker_type . 'Broker';
        $this->broker = new $className();
        if (!$this->broker->init($this->logger)) {
            die(1);
        }
        try {
            $this->broker->connect($this->config->output);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die(2);
        }
        try {
            $this->broker->connect($this->config->input);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die(3);
        }
        $input = join('', $this->config->input);
        $output = join('', $this->config->output);
        $this->repLoop($input, $output);
    }

    public function repLoop($input, $output) {
        while (true) {
            $message = $this->broker->recv($input);

            $this->logger->addDebug('Got message :' . print_r($message, true));
            if ($this->config->ack_required == true) { 
                //need to ack
                $this->logger->addDebug('Acking');
                $this->broker->send($input, 'ack');
            }

            if ($this->config->publish == true) {
                $this->logger->addDebug('Publishing...:' . $message);
                $this->broker->send($output, $message);
                $this->logger->addDebug('Published');
            }
            
            if (class_exists(__NAMESPACE__ . '\\Processor\\' . $this->config->process)) {
                try {
                    $className = __NAMESPACE__ . '\\Processor\\' . $this->config->process;
                    $this->logger->addDebug('Processing with ' . $className);
                    $c = new $className($this->logger, $this->config);
                    $response = $c->process($message);
                } catch (\Exception $e) {
                    $this->logger->addError('Could not process : ' . $e->getMessage());
                    return false;
                }
                try {
                    $this->broker->send($output, $response);
                } catch (\Exception $e) {
                    $this->logger->addError('Could not send response : ' . $e->getMessage());
                }
                try {
                    $this->broker->recv($output);
                } catch (\Exception $e) {
                    $this->logger->addError('Could not recv ack : ' . $e->getMessage());
                }
            }
        }
    }



}

