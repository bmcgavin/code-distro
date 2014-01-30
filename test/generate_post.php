#!/usr/bin/php
<?php

$context = new ZMQContext();
$queue = new ZMQSocket($context, ZMQ::SOCKET_REQ);

$queue->connect("tcp://127.0.0.1:5555");

$payload = new stdClass;
$payload->ref = 'ref/heads/master';
$payload->before = '123';
$payload->after  = '456';
$payload->repository = array(
    'url' => 'http://localhost/nowhere'
);
$payload = json_encode($payload);

try {
        $queue->send(json_encode(array('type' => 'github_hook', 'payload' => $payload)));
} catch (Exception $e) {
        echo $e->getMessage();
}
