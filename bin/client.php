#!/usr/bin/php
<?php

require_once('vendor/autoload.php');

use Codedistro\Client;

if (count($argv) < 2) {
    echo "Need to know what type of client";
    die(1);
}
$config = 'config.ini';
if (count($argv) == 3) {
    echo "Overridden config file, using " . $config . PHP_EOL;
    $config = $argv[2];
}

$s = new Client($config, $argv[1]);

