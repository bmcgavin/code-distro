#!/usr/bin/php
<?php

require_once('vendor/autoload.php');

use Codedistro\Server;

$config = 'config.ini';
if (count($argv) == 2) {
    echo 'Config overridden, using ' . $argv[1] . PHP_EOL;
    $config = $argv[1];
}

$s = new Server($config);