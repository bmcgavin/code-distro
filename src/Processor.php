<?php

namespace Codedistro;

abstract class Processor {

    public $config = null;
    public $logger = null;

    /**
     * For validating the incoming message
     */
    protected $requiredProperties = array();

    /**
     * Storing the data that's been extracted from the incoming message
     */
    public $data = null;

    /**
     * For creating the ongoing message
     */
    protected $next_type = null;
    protected $payload = null;
    protected $status = 'failure';

    public function __construct(\Codedistro\Logger $log, \Codedistro\Config $config) {
        $this->logger = $log;
        $this->config = $config;
    }

    protected function validate(\Codedistro\Message $m) {
        $m->validate($this->requiredProperties);
        $this->data = $m->getData();
        $this->logger->addDebug('data : ' . print_r($this->data, true));
    }

    abstract function process(\Codedistro\Message $message);

    protected function output() {
        if ($this->status == 'failure') {
            $this->type = 'Complete';
        }
        $e = null;
        if (file_exists($this->config->keyLocation)) {
            $e = new Encryption($this->config->keyLocation);
        }
        return new Message(
            $this->logger,
            $this->payload,
            $this->type,
            $e
        );
    }
}
