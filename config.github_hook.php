<?php

$config = array(
    'debug_log' => '/var/log/code_distro/server_github_hook.debug',
    'connect_req_port' => 5555,
    'connect_req_type' => \ZMQ::SOCKET_REQ,
    'bind_rep_port' => 5557,
    'bind_rep_type' => \ZMQ::SOCKET_REP,
    'process'       => 'GithubHook',
    //Processor specific
    'temp_directory' => '/tmp',
);

