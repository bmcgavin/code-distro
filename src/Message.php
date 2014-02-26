<?php

namespace Codedistro;

/**
 * Message class should make a message object given a type
 * and a payload
 */
class Message {

    private $msg = null;
    private $data = null;
    private $logger = null;

    public $type = null;
    public $payload = null;

    static public function getInstance($log, $message) {
        $obj = json_decode($message);
        if (property_exists($obj, 'payload')) {
            $payload = $obj->payload;
        }
        if (property_exists($obj, 'type')) {
            $type = $obj->type;
        }
        return new self($log, $payload, $type);
    }

    public function __construct($log, $payload, $type) {
        $this->logger = $log;
        $this->payload = $payload;
        $this->type = $type;
    }

    public function __toString() {
        return json_encode($this);
    }
    
    public function validate($requirements, $message = null) {
        if ($message === null) {
            $message = $this->payload;
        }
        $this->logger->addDebug('validate requirements input : ' . print_r($requirements, true));
        $this->logger->addDebug('validateArray message input : ' . print_r($message, true));
        foreach($requirements as $key => $value) {
            if (!property_exists($message, $key)) {
                throw new \Exception('$message has no ' . $key);
            }
            if (is_array($value)) {
                $this->validate($value, $message->{$key});
            } else {
                $this->data[$key] = $message->{$key};
            }

        } 
    }

    public function getData() {
        return $this->data;
    }
}
