<?php

$config = array(
    'debug_log' => '/var/log/code_distro/server_github_patch.debug',
    'broker_type' => 'ZeroMQ',
    'input' => array(
        'prot' => 'tcp://',
        'ip' => '127.0.0.1',
        'port' => 5556,
        'type' => \ZMQ::SOCKET_SUB,
        'filter' => '{"type":"github_patch"',
    ),
    'ack_required' => false,
    'output' => array(
        'prot' => 'tcp://',
        'ip' => '127.0.0.1',
        'port' => 5555,
        'type' => \ZMQ::SOCKET_REQ,
    ),
    'publish' => false,
    'process' => 'GithubPatch',
    //Processor specific
    'repo_bmcgavin_cross-words' => '/tmp/patcher/bmcgavin/cross-words',
    'repo_bmcgavin_code-distro' => '/tmp/patcher/bmcgavin/code-distro',
);


