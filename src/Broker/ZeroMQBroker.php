<?php

namespace Codedistro\Broker;

use Codedistro\Broker;

class ZeroMQBroker implements Broker {

    private $ctx = null;
    private $sockets = array();
    private static $log;

    public function init($log) {
        static::$log = $log;
        try {
            $this->ctx = new \ZMQContext();
        } catch (\Exception $e) {
            static::$log->addError('Could not start ZMQ context : ' . $e->getMessage());
            return false;
        }
        static::$log->addDebug('Got 0mq ctx');
        return true;
    }

    public function connect($settings) {
        $sName = join('', $settings);
        static::$log->addDebug('sName : ' . $sName);
        if (array_key_exists($sName, $this->sockets)) {
            return $this->sockets[$sName];
        }
        $this->sockets[$sName] = new \ZMQSocket($this->ctx, $settings['type']);
        static::$log->addDebug($settings['prot'] . $settings['ip'] . ':' . $settings['port']);

        switch($settings['type']) {
        case \ZMQ::SOCKET_SUB:
        case \ZMQ::SOCKET_REQ:
            $this->sockets[$sName]->connect($settings['prot'] . $settings['ip'] . ':' . $settings['port']);
            if ($settings['type'] === \ZMQ::SOCKET_SUB) {
                $filter = '';
                if (array_key_exists('filter', $settings)) {
                    $filter = $settings['filter'];
                }
                $this->sockets[$sName]->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, $filter);
            }
            break;
        case \ZMQ::SOCKET_PUB:
        case \ZMQ::SOCKET_REP:
            $this->sockets[$sName]->bind($settings['prot'] . $settings['ip'] . ':' . $settings['port']);
            break;
        default:
            throw new \Exception('Could not connect : ' . $e->getMessage());
        }
        static::$log->addDebug('Connected to ' . $settings['prot'] . $settings['ip'] . $settings['port']);
    }

    public function recv($selector) {
        return $this->sockets[$selector]->recv();
    }

    public function send($selector, $message) {
        return $this->sockets[$selector]->send($message);
    }

}

