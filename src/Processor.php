<?php

namespace Codedistro;

abstract class Processor {

    public $config = null;
    public $logger = null;

    public $data = null;

    protected $type = null;
    protected $response = null;
    protected $requiredProperties = array();

    public function __construct($log, $config) {
        $this->logger = $log;
        $this->config = $config;

        $this->response = new \stdClass;
        $this->response->type = 'placeholder';
        $this->response->status = 'error';
        $this->response->payload = 'processing error';
    }

    protected function validate($message) {
        $m = new Message($this->logger, $message);
        $m->validate($this->requiredProperties);
        $this->data = $m->getData();
        $this->logger->addDebug('data : ' . print_r($this->data, true));
    }

    abstract function process($message);
}
