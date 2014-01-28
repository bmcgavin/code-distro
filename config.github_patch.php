<?php

$config = array(
    'debug_log' => '/var/log/code_distro/server_github_patch.debug',
    'connect_req_port' => 5555,
    'connect_req_type' => \ZMQ::SOCKET_REQ,
    'bind_rep_port' => 5558,
    'bind_rep_type' => \ZMQ::SOCKET_REP,
    'process'       => 'GithubPatch',
    //Processor specific
    'repo_bmcgavin_cross-words' => '/tmp/patcher/bmcgavin/cross-words',
    'repo_bmcgavin_code-distro' => '/tmp/patcher/bmcgavin/code-distro',
);


