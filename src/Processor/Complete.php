<?php

namespace Codedistro\Processor;

use Codedistro\Processor;
use Codedistro\Message;

class Complete extends Processor {

    public function __construct(\Codedistro\Logger $log, \Codedistro\Config $config) {
        parent::__construct($log, $config);
    }

    public function process(\Codedistro\Message $message) {
        foreach($this->config->addresses as $address) {
            mail($address, 'Complete', $message);
        }

        return null;
    }
}
