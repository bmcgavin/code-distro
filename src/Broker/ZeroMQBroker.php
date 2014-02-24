<?php

namespace Codedistro\Broker;

use Codedistro\Broker;

class ZeroMQBroker implements Broker {

    private $ctx = null;
    private $sockets = array();
    private $logger = null;

    public function init($log) {
        $this->logger = $log;
        try {
            $this->ctx = new \ZMQContext();
        } catch (\Exception $e) {
            $this->logger->addError('Could not start ZMQ context : ' . $e->getMessage());
            return false;
        }
        $this->logger->addDebug('Got 0mq ctx');
        return true;
    }

    public function connect($settings) {
        $filter = '';
        if (array_key_exists('filter', $settings)) {
            $filter = $settings['filter'];
            unset($settings['filter']);
        }
        $settings['type'] = constant($settings['type']);
        $sName = join('', $settings);
        $this->logger->addDebug('sName : ' . $sName);
        if (array_key_exists($sName, $this->sockets)) {
            return $this->sockets[$sName];
        }
        $this->sockets[$sName] = new \ZMQSocket($this->ctx, $settings['type']);
        $this->logger->addDebug($settings['prot'] . $settings['ip'] . ':' . $settings['port']);

        switch($settings['type']) {
        case \ZMQ::SOCKET_SUB:
        case \ZMQ::SOCKET_REQ:
            $this->sockets[$sName]->connect($settings['prot'] . $settings['ip'] . ':' . $settings['port']);
            if ($settings['type'] === \ZMQ::SOCKET_SUB) {
                $this->sockets[$sName]->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, $filter);
            }
            break;
        case \ZMQ::SOCKET_PUB:
        case \ZMQ::SOCKET_REP:
            $this->sockets[$sName]->bind($settings['prot'] . $settings['ip'] . ':' . $settings['port']);
            break;
        default:
            throw new \Exception('Invalid type' . $settings['type']);
        }
        $this->logger->addDebug('Connected to ' . $settings['prot'] . $settings['ip'] . $settings['port']);
        return $sName;
    }

    public function recv($selector) {
        $this->logger->addDebug('recving from selector : ' . $selector);
        return $this->sockets[$selector]->recv();
    }

    public function send($selector, $message) {
        $this->logger->addDebug('sending to selector : ' . $selector);
        return $this->sockets[$selector]->send($message);
    }

}

