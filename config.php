<?php

$config = array(
    'debug_log' => '/var/log/code_distro/server_rep.debug',
    'broker_type' => 'ZeroMQ',
    'input' => array(
        'prot' => 'tcp://',
        'ip' => '127.0.0.1',
        'port' => 5555,
        'type' => \ZMQ::SOCKET_REP,
    ),
    'ack_required' => true,
    'output' => array(
        'prot' => 'tcp://',
        'ip' => '127.0.0.1',
        'port' => 5556,
        'type' => \ZMQ::SOCKET_PUB,
    ),
    'publish'       => true,
    'process'       => false,
);
