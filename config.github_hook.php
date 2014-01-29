<?php

$config = array(
    'debug_log' => '/var/log/code_distro/server_github_hook.debug',
    'broker_type' => 'ZeroMQ',
    'input' => array(
        'prot' => 'tcp://',
        'ip' => '127.0.0.1',
        'port' => 5556,
        'type' => \ZMQ::SOCKET_SUB,
        'filter' => '{"type":"github_hook"',
    ),
    'ack_required' => false,
    'output' => array(
        'prot' => 'tcp://',
        'ip' => '127.0.0.1',
        'port' => 5555,
        'type' => \ZMQ::SOCKET_REQ,
    ),
    'publish'     => false,
    'process'       => 'GithubHook',
    //Processor specific
    'temp_directory' => '/tmp',
);

