<?php

$fh = fopen('/var/www/ghcb.linuxplicable.org/logs/log', 'a');
fwrite($fh, 'Got message : ' . $_POST['payload'] . PHP_EOL);

$context = new ZMQContext();
$queue = new ZMQSocket($context, ZMQ::SOCKET_REQ);

$queue->connect("tcp://127.0.0.1:5555");

require '/tmp/patcher/bmcgavin/code-distro/src/Encryption.php';

$type = 'GithubHook';

$_POST['payload'] = json_decode($_POST['payload']);

//Change message type based on format
if (property_exists($_POST['payload'], 'canon_url')) {
    $type = 'BitbucketHook';
}

$e = new \Codedistro\Encryption('/etc/code-distro/key');
$_POST['payload'] = $e->encrypt($_POST['payload']);


try {
	$queue->send(json_encode(array('type' => $type, 'payload' => $_POST['payload'])));
} catch (Exception $e) {
	error_log($e->getMessage());
}
fwrite($fh, 'Sent' . PHP_EOL);
fclose($fh);

