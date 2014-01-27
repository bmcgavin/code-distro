<?php

$config = array(
    'debug_log' => '/var/log/code_distro/server_rep.debug',
    'bind_rep_port' => 5555,
    'bind_rep_type' => \ZMQ::SOCKET_REP,
    'bind_pub_port' => 5556,
    'bind_pub_type' => \ZMQ::SOCKET_PUB,
);
