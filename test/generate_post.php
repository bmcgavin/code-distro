#!/usr/bin/php
<?php

$context = new ZMQContext();
$queue = new ZMQSocket($context, ZMQ::SOCKET_REQ);

$queue->connect("tcp://127.0.0.1:5555");

$message = new stdClass;
$message->type = 'github_hook';

if (array_key_exists(1, $argv)) {
    $payload = json_decode($argv[1]);
} else {
    $payload = new stdClass;
    $payload->ref = 'ref/heads/master';
    $payload->before = '123';
    $payload->after  = '456';
    $payload->repository = array(
        'url' => 'http://localhost/nowhere'
    );
}
$message->payload = $payload;

try {
        $queue->send(json_encode($message));
} catch (Exception $e) {
        echo $e->getMessage();
}
