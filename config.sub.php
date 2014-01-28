<?php

$config = array(
    'debug_log' => '/var/log/code_distro/client_rep.debug',
    'connect_sub_port' => 5556,
    'connect_sub_type' => \ZMQ::SOCKET_SUB,
    'github_hook_port' => 5557,
    'github_patch_port' => 5558,
);

