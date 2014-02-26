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
    }

    protected function validate(\Codedistro\Message $m) {
        $m->validate($this->requiredProperties);
        $this->data = $m->getData();
        $this->logger->addDebug('data : ' . print_r($this->data, true));
    }

    abstract function process(\Codedistro\Message $message);
}
