<?php

$context = new ZMQContext();
$queue = new ZMQSocket($context, ZMQ::SOCKET_REQ);

$queue->connect("tcp://127.0.0.1:5555");

try {
    $queue->send(json_encode(array('type' => 'github_hook', 'payload' => $_POST['payload'])));
} catch (Exception $e) {
    error_log($e->getMessage());
}

