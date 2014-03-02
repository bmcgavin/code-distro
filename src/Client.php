<?php

/**
 * Codedistro - distribute git repository patches over 0mq
 */

namespace Codedistro;

use Codedistro\Broker\ZeroMQBroker;


/**
 * Client - get the data, process it, send back to the server
 */

class Client {

    public $config = null;
    public $logger = null;
    public $broker = null;

    public function __construct($config, $prefix) {
        try {
            $this->config = new Config($config, $prefix);
        } catch (\Exception $e) {
            $error = __CLASS__ . ": Could not initiate config : " . $e->getMessage();
            error_log($error);
            die(1);
        }
        try {
            $this->logger = new Logger($this->config->logFile, 'Client.' . $prefix, $this->config->logLevel);
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
            //Subscribe - get incoming
            $config = $this->config->clientIncoming[$this->config->brokerType];
            $config['filter'] = $this->parseFilter($prefix);
            $this->logger->addDebug(print_r($config, true));
            $input = $this->broker->connect($config);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die(2);
        }
        try {
            //Reply comms - get outgoing
            $config = $this->config->clientOutgoing[$this->config->brokerType];
            $output = $this->broker->connect($config);
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
            die(3);
        }
        $this->repLoop($input, $output);
    }

    private function parseFilter($prefix) {
        $this->logger->addDebug('parsing filter for ' . $prefix);
        $key = $this->config->brokerType . '.filter';
        return str_replace('__name__', $prefix, $this->config->{$key});
    }

    public function repLoop($input, $output) {
        while (true) {
            $message = $this->broker->recv($input);

            $this->logger->addDebug('Got message :' . print_r($message, true));

            $e = null;
            if (file_exists($this->config->keyLocation)) {
                $e = new Encryption($this->config->keyLocation);
            } else {
                $this->logger->addWarning('Encryption disabled, ' . $this->config->keyLocation . ' does not exist');
            }
            $m = Message::getInstance($this->logger, $message, $e);
            unset($e);

            if (class_exists(__NAMESPACE__ . '\\Processor\\' . $this->config->processor)) {
                try {
                    $className = __NAMESPACE__ . '\\Processor\\' . $this->config->processor;
                    $this->logger->addDebug('Processing with ' . $className);
                    $c = new $className($this->logger, $this->config);
                    $response = $c->process($m);
                } catch (\Exception $e) {
                    $this->logger->addError('Could not process : ' . $e->getMessage());
                    return false;
                }
                //Do I have a response to send?
                if ($response !== null) {
                    try {
                        $this->logger->addDebug('Trying to send : ' . $response, true);
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
            } else {
                $this->logger->addDebug("Don't know how to process");
            }
        }
    }



}


