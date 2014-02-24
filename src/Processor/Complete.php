<?php

namespace Codedistro\Processor;

use Codedistro\Processor;
use Codedistro\Message;

class Complete extends Processor {

    public function __construct($log, $config) {
        parent::__construct($log, $config);
    }

    public function process($message) {
        foreach($this->config->addresses as $address) {
            mail($address, 'Complete', $message);
        }

        return null;
    }
}
