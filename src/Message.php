<?php

namespace Codedistro;

class Message {

    private $msg = null;
    private $type = null;
    private $payload = null;
    private $data = null;
    private $logger = null;

    public function __construct($log, $message = null) {
        $this->logger = $log;
        $this->payload = new \stdClass;
        if ($message !== null) {
            $this->msg = json_decode($message);
            if ($this->msg === null) {
                throw new \Exception('message is not valid JSON');
            }
            if (!property_exists($this->msg, 'type')) {
                throw new \Exception('message has no type');
            }
            if (!property_exists($this->msg, 'payload')) {
                throw new \Exception('message has no payload');
            }
            $this->type = $this->msg->type;
            $this->payload = json_decode($this->msg->payload);
            if ($this->payload === null) {
                throw new \Exception('Payload is not valid JSON');
            }
        }
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
